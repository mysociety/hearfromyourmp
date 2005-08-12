--
-- schema.sql:
-- Schema for Your Constituency Mailing List
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.5 2005-08-12 17:46:06 matthew Exp $
--

-- Returns the timestamp of current time, but with possibly overriden "today".
create function pb_current_timestamp()
    returns timestamp as '
    begin
        return current_timestamp;
    end;
' language 'plpgsql';

create table constituent (
    id serial not null primary key,
-- For old-style signups. TODO: Remove this when everyone switched over
    name text,
    email text,
-- For new-style signups
    person_id integer not null references person(id),
-- Constituency they've signed up to, plus postcode they used
    constituency integer not null default 0,
    postcode text not null,
-- Metadata
    creation_time timestamp not null default current_timestamp,
    creation_ipaddr text not null
);

create index constituent_person_id_idx on constituent(person_id);
create unique index constituent_person_id_constituency_idx on constituent(person_id, constituency);

-- secret
-- A random secret.
create table secret (
    secret text not null
);

-- users, but call the table person rather than user so we don't have to quote
-- its name in every statement....
create table person (
    id serial not null primary key,
    name text,
    email text not null,
    password text,
    website text,
-- If this person is an MP, this is the ID of the constituency of where
    mp_for integer,
    numlogins integer not null default 0
);

create unique index person_email_idx on person(email);

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
    extra text
);

-- make expiring old requests quite quick
create index requeststash_whensaved_idx on requeststash(whensaved);

create table message (
    id serial not null primary key,
    constituency integer not null,
    posted timestamp not null default current_timestamp,
    subject text not null,
    content text not null
);

create table comment (
    id text not null primary key,   -- comment ID, 8 hex digits
    message integer not null references message(id),
    refs text not null default '',
    person_id integer not null references person(id),
    date timestamp not null default current_timestamp,
    ipaddr text not null,
    content text not null,
    visible integer not null default 0
);
create index comment_refs_idx on comment(refs)

create table message_sent (
    person_id integer references person(id),
    message_id integer references message(id),
    comment_id integer references comment(id),
    whenqueued timestamp not null default pb_current_timestamp()
);

create index message_sent_person_id_idx on message_sent(person_id);
create index message_sent_message_id_idx on message_sent(message_id);
create index message_sent_comment_id_idx on message_sent(comment_id);
create unique index message_sent_message_unique_idx on message_sent(person_id, message_id);
create unique index message_sent_comment_unique_idx on message_sent(person_id, comment_id);
