#!/usr/bin/perl -w
use strict;

# This application will take an input of a MARC file or plaintext file and extact unique identifiers to be queried in Voyager.
# The identifiers are matched against the 035$a MARC field.  Voyager's BIB IDs are returned as a text list.
# This script leverages the connection information from the ShelfLister configuration.

use CGI;
use DBI;
use MARC::Batch;
use HTML::Entities;

my($q) = new CGI;

use lib '../shelflister';

# Read in base configuration file
unless (eval qq(require "ShelfLister.ini")) {
	die "Couldn't load required config file: ".$@;
}
# Voyager SID
$ENV{ORACLE_SID} = "$ShelfListerIni::oracle_sid";
# ORACLE_HOME
$ENV{ORACLE_HOME} = "$ShelfListerIni::oracle_home";

my($myURI) = $q->url( -relative => 1 );
my($select001) = $q->param('marcfield') && $q->param('marcfield') eq '035$a' ? '' : 'checked="checked"';
my($select035) = $select001 ? '' : 'checked="checked"';
my $output = <<"EOF";
<p>Upload a MARC file or a text file containing one identifier per line to lookup matching 035\$a fields in Voyager.  If using a MARC file, select which field(s) you wwant to match against existing 035\$a fields.</p>
<form enctype="multipart/form-data" method="post" action="$myURI">
<div>
<label for="marcfile">MARC file</label><input id="marcfile" type="file" name="marcfile" />
</div>
<div>
<input id="marcfield_001" type="radio" name="marcfield" value="001/003" $select001 />
<label for="marcfield_001">001 and 003</label>
<input id="marcfield_035" type="radio" name="marcfield" value="035\$a" $select035 />
<label for="marcfield_035">035\$a</label>
</div>
<div>
<label for="textfile">Text file</label><input id="textfile" type="file" name="textfile" />
</div>
<div>
<input type="submit" name="submit" value="Submit"/>
</div>
</form>
EOF

if ($q->param('submit')) {

	my(%extIds);
	my($anySkipped);
	my $file;
	if ($q->param('marcfile')) {
		$file = $q->upload( 'marcfile' );
		my $batch = MARC::Batch->new( 'USMARC', $q->tmpFileName($file) );
		if ($batch) {
			while ( my $marc = $batch->next ) {
				if ($marc) {
					if ($q->param('marcfield') eq '035$a') {
						foreach my $field ($marc->field('035')) {
							if ($field->subfield('a') =~ /\((.+)\)(.+)/) {
								$extIds{$2} = $1;
							}
						}
					} else {
						$extIds{$marc->field('001')->as_string()} = $marc->field('003')->as_string();
					}
				} else {
					$anySkipped = 1;
				}
			}
		}
		if ( !$file && $q->cgi_error ) {
			print $q->header( -status => $q->cgi_error );
			exit 0;
		}

	}
	if ($q->param('textfile')) {
		$file = $q->upload( 'textfile' );
		my($i);
		while ( $i = <$file> ) {
			chomp $i;
			$i =~ s/^\s+//;
			$i =~ s/\s+$//;
			if ($i ne "") {
				if ($i =~ m/^[(](.*?)[)](.*)$/) {
					$extIds{$2} = $1;
				} else {
					$extIds{$i} = '';
				}
			}
		}
		if ( !$file && $q->cgi_error ) {
			print $q->header( -status => $q->cgi_error );
			exit 0;
		}

	}

	if ( scalar(%extIds) ) {
		my $buffer;
		my $oracle_host_info = '';
		if ($ShelfListerIni::oracle_server) {
			$oracle_host_info = "host=$ShelfListerIni::oracle_server;SID=$ShelfListerIni::oracle_sid";
			if ($ShelfListerIni::oracle_listener_port) {
				$oracle_host_info .= ";port=$ShelfListerIni::oracle_listener_port";
			}
		}
		my $dbh = DBI->connect("dbi:Oracle:$oracle_host_info", $ShelfListerIni::oracle_username, $ShelfListerIni::oracle_password);
		if ($dbh) {
			my $sth;
			if ($sth = $dbh->prepare("select bib_id, display_heading from ".$ShelfListerIni::db_name.".bib_index where index_code = '035A' and normal_heading = ?")) {
				$output = '';
				my(@bibids);
				my($anySuccess);
				foreach my $xId (keys(%extIds)) {
					$anySuccess = 1;
					if ($sth->execute(uc($xId))) {
						my @rs;
						if (@rs = $sth->fetchrow_array()) {
							if ($extIds{$xId} && $rs[1] ne '('.$extIds{$xId}.')'.$xId) {
								$output .= '<p class="error warning">Fuzzy match on '.encode_entities($rs[1]).' for '.'('.encode_entities($extIds{$xId}.')'.$xId).'</p>';
							}
							push(@bibids, $rs[0]);
						} else {
							$output = '<p class="error">No match in Voyager Database for: '.'('.encode_entities($extIds{$xId}.')'.$xId).'</p>'.$output;
						}
					} else {
						$output = '<p class="error">Failed to query Voyager Database for: '.encode_entities($xId).'</p>'.$output;
					}
				}
				if ($anySuccess) {
					$output = '<p class="success">Successful match for '.scalar(@bibids).' records, listed below.</p>'.$output.'<pre>'.join("\n", sort(@bibids)).'</pre>';
					if ($anySkipped) {
						$output = '<p class="error">One or more records were invalid in the MARC file.</p>'.$output;
					}
				} else {
					$output = '<p class="error">Failed to process MARC file.</p>';
				}
			} else {
				$output = '<p class="error">Failed to prepare Voyager Database query.</p>'.$output;
			}
		} else {
			$output = '<p class="error">Failed to connect to the Voyager Database.</p>'.$output;
		}
	}
}

my($title) = 'Lookup BIB_ID via 035$a';
print $q->header('text/html');
print <<"EOF";
<html>
<head>
<title>$title</title>
</head>
<body>
<h1>$title</h1>
$output
</body>
EOF
