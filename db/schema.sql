--
-- schema.sql:
-- Schema for HearFromYourMP/Councillor
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.31 2007-09-18 12:58:30 matthew Exp $
--

-- Returns the timestamp of current time, but with possibly overriden "today".
create function ms_current_timestamp()
    returns timestamp as '
    begin
        return current_timestamp;
    end;
' language 'plpgsql';

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

-- An area's constituents who have signed up
create table constituent (
    id serial not null primary key,
-- For old-style signups. TODO: Remove this when everyone switched over
    name text,
    email text,
-- For new-style signups
    person_id integer not null references person(id),
-- Constituency they've signed up to, plus postcode they used, and whether they're the current rep.
    area_id integer, -- can be NULL if postcode is bad
    postcode text not null,
    is_rep boolean not null default false,
-- Metadata
    creation_time timestamp not null default current_timestamp,
    creation_ipaddr text not null
);

create index constituent_person_id_idx on constituent(person_id);
create index constituent_area_id_idx on constituent(area_id);
create unique index constituent_person_id_area_id_idx on constituent(person_id, area_id);

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
    area_id integer not null,
    rep_id integer not null, -- Which rep in this area posted the message
    posted timestamp not null default current_timestamp,
    subject text not null,
    content text not null,
    -- Messages start in state 'new'; then they are mailed to the MP's
    -- registered address for approval, moving in to state 'ready'. Once the
    -- MP's assistant clicks on the link in the confirmation mail, they move
    -- to 'approved' and are sent.
    state text not null default ('new') check (state in ('new', 'ready', 'approved'))
);
create index message_state_area_id_idx on message(state,area_id);

create table comment (
    id text not null primary key,   -- comment ID, 8 hex digits
    message integer not null references message(id),
    refs text not null default '',
    person_id integer not null references person(id),
    date timestamp not null default current_timestamp,
    ipaddr text not null,
    content text not null,
    visible integer not null default 0,
    posted_by_rep boolean not null default false
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

-- rep_threshold NUM DIR SCALE
-- If DIR is positive, return the smallest threshold level larger than NUM;
-- otherwise return the largest threshold level smaller than NUM.
-- SCALE is so we can use different thresholds for different sorts of rep.
create function rep_threshold(integer, integer, integer) returns integer as '
    declare
        num alias for $1;
        dir alias for $2;
	scale alias for $3;
        n integer;
        m integer;
    begin
        if num < scale * 4 then
            m := scale;
        elsif num < scale * 8 then
            m := 2 * scale;
        else
            m := 4 * scale;
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

create table rep_threshold_alert (
    area_id integer not null,
    whensent timestamp not null default current_timestamp,
    num_subscribers integer not null -- at time of sending
);

create index rep_threshold_alert_area_id_idx
    on rep_threshold_alert(area_id);

create table rep_nothanks (
    area_id integer not null,
    status boolean not null,
    website text,
    gender text not null
);

create unique index rep_nothanks_area_id_idx on rep_nothanks(area_id);
 
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

-- cached information from MaPit about areas
create table area_cache (
    id integer not null primary key,
    name text not null
);

-- cached information from DaDem about reps
create table rep_cache (
    id integer not null primary key,
    name text not null,
    created integer not null,
    area_id integer not null,
    -- Email to which confirmation requests and some alerts? are sent
    confirmation_email text not null default ''
);
