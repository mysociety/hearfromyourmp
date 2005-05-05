--
-- schema.sql:
-- Schema for Your Constituency Mailing List
--
-- Copyright (c) 2005 UK Citizens Online Democracy. All rights reserved.
-- Email: francis@mysociety.org; WWW: http://www.mysociety.org/
--
-- $Id: schema.sql,v 1.1 2005-05-05 01:01:23 francis Exp $
--

create table constituent (
    id serial not null primary key,

    name text not null,
    email text not null,
    postcode text not null,

    creation_time timestamp not null,
    creation_ipaddr text not null
);

