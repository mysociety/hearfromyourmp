--
-- schema.sql:
-- Schema for Your Constituency Mailing List
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.28 2007-05-29 17:50:34 francis Exp $
--

-- Returns the timestamp of current time, but with possibly overriden "today".
create function ms_current_timestamp()
    returns timestamp as '
    begin
        return current_timestamp;
    end;
' language 'plpgsql';

-- information about a constituency (and its MP)
create table constituency (
    -- MaPit area_id
    id integer not null primary key,
    -- Email to which confirmation requests for posted messages are sent;
    -- roughly, "the email address of the person who may post messages to the
    -- site pp an MP".
    confirmation_email text not null
);

-- users, but call the table person rather than user so we don't have to quote
-- its name in every statement....
create table person (
    id serial not null primary key,
    name text,
    email text not null,
    password text,
    website text,
    numlogins integer not null default 0
);

create unique index person_email_idx on person(email);
create unique index person_email_lower_idx on person(lower(email));

-- MP's constituents who have signed up
create table constituent (
    id serial not null primary key,
-- For old-style signups. TODO: Remove this when everyone switched over
    name text,
    email text,
-- For new-style signups
    person_id integer not null references person(id),
-- Constituency they've signed up to, plus postcode they used, and whether they're the current rep.
    constituency integer, -- can be NULL if postcode is bad
    postcode text not null,
    is_mp boolean not null default false,
-- Metadata
    creation_time timestamp not null default current_timestamp,
    creation_ipaddr text not null
);

create index constituent_person_id_idx on constituent(person_id);
create index constituent_constituency_idx on constituent(constituency);
create unique index constituent_person_id_constituency_idx on constituent(person_id, constituency);

-- secret
-- A random secret.
create table secret (
    secret text not null
);

-- Stores randomly generated tokens and serialised hash arrays associated
-- with them.
create table token (
    scope text not null,        -- what bit of code is using this token
    token text not null,
    data bytea not null,
    created timestamp not null default current_timestamp,
    primary key (scope, token)
);

create table requeststash (
    key char(16) not null primary key,
    whensaved timestamp not null default current_timestamp,
    method text not null default 'GET' check (
            method = 'GET' or method = 'POST'
        ),
    url text not null,
    -- contents of POSTed form
    post_data bytea check (
            (post_data is null and method = 'GET') or
            (post_data is not null and method = 'POST')
        ),
    extra text,
    -- email address of user in the stash data
    email text
);

-- make expiring old requests quite quick
create index requeststash_whensaved_idx on requeststash(whensaved);

create table message (
    id serial not null primary key,
    constituency integer not null,
    posted timestamp not null default current_timestamp,
    subject text not null,
    content text not null,
    -- Messages start in state 'new'; then they are mailed to the MP's
    -- registered address for approval, moving in to state 'ready'. Once the
    -- MP's assistant clicks on the link in the confirmation mail, they move
    -- to 'approved' and are sent.
    state text not null default ('new') check (state in ('new', 'ready', 'approved'))
);

create table comment (
    id text not null primary key,   -- comment ID, 8 hex digits
    message integer not null references message(id),
    refs text not null default '',
    person_id integer not null references person(id),
    date timestamp not null default current_timestamp,
    ipaddr text not null,
    content text not null,
    visible integer not null default 0,
    posted_by_mp boolean not null default false
);
create index comment_refs_idx on comment(refs);
create index comment_person_id_idx on comment(person_id);
create index comment_date_idx on comment(date);

create sequence comment_id_seq;
create function comment_next_id()
    returns text as '
select lpad(to_hex(nextval(''comment_id_seq'')), 8, ''0'')
' language sql;

-- For alerting MPs about comments made
create table comment_sent (
    comment_id text not null references comment(id),
    whenqueued timestamp not null default current_timestamp
);

create table message_sent (
    person_id integer references person(id),
    message_id integer references message(id),
    whenqueued timestamp not null default current_timestamp
);

create index message_sent_person_id_idx on message_sent(person_id);
create index message_sent_message_id_idx on message_sent(message_id);
create unique index message_sent_message_unique_idx on message_sent(person_id, message_id);

create table alert (
    id serial not null primary key,
    person_id integer not null references person(id),
    message_id integer not null references message(id),
    whensubscribed timestamp not null default current_timestamp
);
create index alert_person_id_idx on alert(person_id);
create index alert_message_id_idx on alert(message_id);
create unique index alert_unique_idx on alert(person_id, message_id);

create table alert_sent (
    alert_id integer not null references alert(id),
    comment_id text not null references comment(id),
    whenqueued timestamp not null default current_timestamp
);
create index alert_sent_id_idx on alert_sent(alert_id);
create index alert_sent_comment_id_idx on alert_sent(comment_id);
create unique index alert_sent_unique_idx on alert_sent(alert_id, comment_id);

-- mp_threshold NUM DIR
-- If DIR is positive, return the smallest threshold level larger than NUM;
-- otherwise return the largest threshold level smaller than NUM.
create function mp_threshold(integer, integer) returns integer as '
    declare
        num alias for $1;
        dir alias for $2;
        n integer;
        m integer;
    begin
        if num < 100 then
            m := 25;
        elsif num < 200 then
            m := 50;
        else
            m := 100;
        end if;
        n := num / m;
        if dir < 0 then
            if n = 0 then
                return null;
            else
                return n * m;
            end if;
        elsif dir > 0 then
            return (n + 1) * m;
        else
            raise exception ''DIR should be positive or negative, not 0'';
        end if;
    end;
' language 'plpgsql';

create table mp_threshold_alert (
    -- XXX This is broken because it's per-constituency, not per-MP. So if the
    -- MP for a constituency changes and the previous MP had already sent
    -- mails to their constituents, the new MP won't get chivvying mail. But
    -- ignore that problem for the moment.
    constituency integer not null,
    whensent timestamp not null default current_timestamp,
    num_subscribers integer not null -- at time of sending
);

create index mp_threshold_alert_constituency_idx
    on mp_threshold_alert(constituency);

create table mp_nothanks (
    constituency integer not null,
    status boolean not null,
    website text,
    gender text not null
);

create unique index mp_nothanks_constituency_idx on mp_nothanks(constituency);
 
-- table of abuse reports on comments
create table abusereport (
    id serial not null primary key,
    comment_id text not null references comment(id),
    reason text,
    whenreported timestamp not null default ms_current_timestamp(),
    ipaddr text,
    email text
);

create index abusereport_comment_id_idx on abusereport(comment_id);

create function delete_comment(text)
    returns void as '
    begin
        delete from abusereport where comment_id = $1;
        delete from alert_sent where comment_id = $1;
        delete from comment_sent where comment_id = $1;
        delete from comment where id = $1;
        return;
    end
' language 'plpgsql';

create table constituency_cache (
    id integer not null primary key,
    name text not null,
    rep_name text not null default '',
    rep_id integer not null default 0 
);
