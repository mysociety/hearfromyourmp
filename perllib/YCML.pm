#!/usr/bin/perl
#
# YCML.pm:
# Various YCML bits.
#
# Copyright (c) 2007 UK Citizens Online Democracy. All rights reserved.
# Email: francis@mysociety.org; WWW: http://www.mysociety.org/
#
# $Id: YCML.pm,v 1.1 2007-06-04 13:35:19 francis Exp $
#

package YCML::Error;

use strict;
use Error qw(:try);
@YCML::Error::ISA = qw(Error::Simple);

package YCML::DB;

use strict;

use mySociety::Config;
use mySociety::DBHandle qw(dbh);
use mySociety::Util;
use DBI;

BEGIN {
    mySociety::DBHandle::configure(
            Name => mySociety::Config::get('YCML_DB_NAME'),
            User => mySociety::Config::get('YCML_DB_USER'),
            Password => mySociety::Config::get('YCML_DB_PASS'),
            Host => mySociety::Config::get('YCML_DB_HOST', undef),
            Port => mySociety::Config::get('YCML_DB_PORT', undef),
            OnFirstUse => sub {
                if (!dbh()->selectrow_array('select secret from secret')) {
                    local dbh()->{HandleError};
                    dbh()->do('insert into secret (secret) values (?)',
                                {}, unpack('h*', random_bytes(32)));
                    dbh()->commit();
                }
            }
        );
}

=item secret

Return the site shared secret.

=cut
sub secret () {
    return scalar(dbh()->selectrow_array('select secret from secret'));
}

1;
