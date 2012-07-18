#!/usr/bin/env perl
# vim: expandtab:ts=4
use strict;
use warnings;

use Capture::Tiny qw(capture);
use Cwd qw(abs_path realpath);
use File::Basename;
use File::Path;
use File::Spec::Functions;
use File::Temp qw(tempdir);
use FindBin;
use Test::More tests => 34;

=head1 NAME

10-pulseserver-git.t - basic acceptance test for pulseserver-git

=head1 SYNOPSIS

  prove ./10-pulseserver-git.t

Run a shallow test of pulseserver-git.
Not comprehensive - more of a sanity check.

=cut

my $SCRIPT = abs_path( catfile( $FindBin::Bin, qw(.. pulseserver-git) ) );

# Run some tests using dry-run mode and explicitly calling the script.
# No contact with real git servers.
sub run_fake_tests
{
    local $ENV{HOME} = realpath tempdir( basename($0).'-home.XXXXXX', TMPDIR => 1, CLEANUP => 1 );

    my $status;
    my $stdout;
    my $stderr;



    ###########################################################################
    # Control: `--help' just prints something to stderr and exits.
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--help' );
    };

    ok( $status, 'explicit: --help exits non-zero' );

    like( $stderr, qr{pulseserver-git.*maintain-cache}xms, 'explicit: --help output looks like not passed to git' );

    is( $stdout, q{}, 'explicit: no unexpected output from --help' );



    ###########################################################################
    # `--version' passed through to git, silently.
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--version' );
    };

    ok( !$status, 'explicit: --version exits zero' );

    like( $stdout, qr{\A git\ version\ [^\s]+ \n \z}xms, 'explicit: --version looks like passed through to git, no log' );

    is( $stderr, q{}, 'explicit: no unexpected error from --version' );



    ###########################################################################
    # clone command which looks wrong is passed through to git, with warning
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, 'clone', '--quiet', '--some-nonexistent-thing' );
    };

    ok( $status, 'explicit: clone hi-there exits non-zero' );

    is( $stdout, q{}, 'explicit: no output from clone hi-there' );

    like( $stderr, qr{
            \A
            \QQtQA::App::PulseServerGit: clone command `clone --quiet --some-nonexistent-thing' is not understood.  Cache won't be used.\E
            \n
            \Qerror: unknown option `some-nonexistent-thing'\E
            \n
        }xms, 'explicit: clone hi-there error as expected' );



    #############################################################################
    # clone command which looks right is not intercepted when cache not exist yet
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--dry-run', 'clone', '--no-checkout', 'some/repo', 'somefolder' );
    };

    ok( !$status, 'explicit: dry-run cacheless clone exits 0' );

    is( $stdout, q{}, 'explicit: no output from dry-run cacheless clone' );

    is( $stderr, qq{QtQA::App::PulseServerGit: commandline `clone --no-checkout some/repo somefolder' looks like a clone }
                .qq{which we'd like to intercept, but the git object cache doesn't seem to exist yet }
                .qq|($ENV{HOME}/pulse/git-object-cache/README was not found).  `pulseserver-git |
                .qq{maintain-cache' probably needs to be run.  Cache won't be used.\n}
                .qq{QtQA::App::PulseServerGit: + [dry-run] /usr/bin/git clone --no-checkout some/repo somefolder\n},
                'explicit: dry-run cacheless stderr as expected' );



    #############################################################################
    # make the cache - dry run
    #
    my $CACHEDIR = catfile( $ENV{ HOME }, qw(pulse git-object-cache) );
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--dry-run', 'maintain-cache' );
    };

    ok( !$status, 'explicit: dry-run maintain-cache exits 0' );

    is( $stdout, q{}, 'explicit: no output from dry-run maintain-cache' );

    is( $stderr, <<"EOF"
QtQA::App::PulseServerGit: + [dry-run] rm -rf $CACHEDIR
QtQA::App::PulseServerGit: + [dry-run] mkdir -p $CACHEDIR
QtQA::App::PulseServerGit: + [dry-run] /usr/bin/git --git-dir=$CACHEDIR init
QtQA::App::PulseServerGit: + [dry-run] /usr/bin/git --git-dir=$CACHEDIR config gc.auto 0
QtQA::App::PulseServerGit: + [dry-run] /bin/sh -c echo 'Created by $SCRIPT - DO NOT MESS WITH THIS DIRECTORY!' > $ENV{HOME}/pulse/git-object-cache/README
EOF
        , 'explicit: dry-run maintain-cache stderr as expected' );

    ok( (! -d $CACHEDIR ), 'explicit: dry-run maintain-cache did not make the cache' );



    #############################################################################
    # make the cache, really
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, 'maintain-cache' );
    };

    ok( !$status, 'explicit: maintain-cache exits 0' );

    is( $stdout, qq{Initialized empty Git repository in $CACHEDIR/\n}, 'explicit: no output from maintain-cache' );

    is( $stderr, <<"EOF"
QtQA::App::PulseServerGit: + rm -rf $ENV{HOME}/pulse/git-object-cache
QtQA::App::PulseServerGit: + mkdir -p $ENV{HOME}/pulse/git-object-cache
QtQA::App::PulseServerGit: + /usr/bin/git --git-dir=$ENV{HOME}/pulse/git-object-cache init
QtQA::App::PulseServerGit: + /usr/bin/git --git-dir=$ENV{HOME}/pulse/git-object-cache config gc.auto 0
QtQA::App::PulseServerGit: + /bin/sh -c echo 'Created by $SCRIPT - DO NOT MESS WITH THIS DIRECTORY!' > $ENV{HOME}/pulse/git-object-cache/README
EOF
        , 'explicit: maintain-cache stderr as expected' );

    ok( (-f "$CACHEDIR/README" ), 'explicit: maintain-cache made the cache' );



    #############################################################################
    # maintain-cache again should do nothing
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, 'maintain-cache' );
    };

    ok( !$status, 'explicit: maintain-cache no-op exits 0' );

    is( $stdout, q{}, 'explicit: no output from maintain-cache no-op' );

    is( $stderr, q{}, 'explicit: no stderr from maintain-cache no-op' );



    #############################################################################
    # now try a clone again, this time with the cache expected to be used
    #
    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--dry-run', 'clone', '--no-checkout', 'some/repo', 'somefolder' );
    };

    ok( !$status, 'explicit: dry-run cacheful clone exits 0' );

    is( $stdout, q{}, 'explicit: no output from dry-run cacheful clone' );

    is( $stderr, qq{QtQA::App::PulseServerGit: + [dry-run] /usr/bin/git clone --verbose --reference $CACHEDIR --no-checkout some/repo somefolder\n},
        'explicit: dry-run cacheful clone output as expected' );



    #############################################################################
    # try a maintain-cache with some existing scm dirs
    #
    my $pulseprojectdir = catfile( $ENV{HOME}, qw(.pulse2 data projects) );
    foreach my $cmd_ref (
        [ 'mkdir', '-p', "$pulseprojectdir/123/scm" ],
        [ 'git', "--git-dir=$pulseprojectdir/123/scm/.git", 'init' ],
        [ 'mkdir', '-p', "$pulseprojectdir/456/scm" ],
        [ 'git', "--git-dir=$pulseprojectdir/456/scm/.git", 'init' ],
    ) {
        (0 == system( @{ $cmd_ref } )) || die "@{ $cmd_ref } exited with status $?";
    }

    ($stdout, $stderr) = capture {
        $status = system( $SCRIPT, '--dry-run', 'maintain-cache' );
    };

    ok( !$status, 'explicit: dry-run maintain-cache update exits 0' );

    is( $stdout, q{}, 'explicit: no output from dry-run maintain-cache update' );

    is( $stderr, <<"EOF"
QtQA::App::PulseServerGit: + [dry-run] ionice -n7 nice git --git-dir=$CACHEDIR fetch $pulseprojectdir/123/scm +refs/*:refs/project_123/*
QtQA::App::PulseServerGit: + [dry-run] ionice -n7 nice git --git-dir=$CACHEDIR fetch $pulseprojectdir/456/scm +refs/*:refs/project_456/*
EOF
        , 'explicit: dry-run maintain-cache update stderr as expected' );

    return;
}

# Run some tests doing real clone(s) from real repo(s)
# This is a manual test, as someone needs to watch the result to see if there was really
# a performance / disk space benefit.
sub run_real_tests
{
    diag( 'Going to run tests using real remote git repos.  May be slow or unstable!' );

    local $ENV{HOME} = tempdir( basename($0).'-home.XXXXXX', TMPDIR => 1, CLEANUP => 1 );

    my $linkdir = tempdir( basename($0).'-bin.XXXXXX', TMPDIR => 1, CLEANUP => 1 );

    (0 == system('ln', '-s', $SCRIPT, catfile( $linkdir, 'git' ))) || die "symlinking git failed with status $?";

    local $ENV{PATH} = "$linkdir:$ENV{PATH}";

    # test repo should be big, but not too big ...
    my $repo = 'git://scm.dev.nokia.troll.no/qt/qtbase.git';
    my $pulse_dest1 = catfile( $ENV{HOME}, qw(.pulse2 data projects 1234 scm) );
    my $pulse_dest2 = catfile( $ENV{HOME}, qw(.pulse2 data projects 5678 scm) );

    mkpath( dirname( $pulse_dest1 ) );

    my $status;
    my $then;
    my $now;


    #############################################################################
    # make the cache
    #
    $then = time;
    $status = system( $SCRIPT, 'maintain-cache' );
    $now = time;
    ok( !$status, 'real tests: maintain-cache succeeded' );
    # don't verify all the output, that's done elsewhere
    diag( 'maintain-cache runtime: '.($now-$then).' seconds' );

    #############################################################################
    # do a clone into pulse scm dir (simulate a pulse clone)
    #
    $then = time;
    $status = system( 'git', 'clone', '--no-checkout', $repo, $pulse_dest1 );
    $now = time;
    ok( !$status, 'real tests: initial git clone succeeded' );
    diag( 'initial clone runtime: '.($now-$then).' seconds' );
    system( 'du', '-shx', $pulse_dest1 );

    #############################################################################
    # update the cache
    #
    $then = time;
    $status = system( $SCRIPT, 'maintain-cache' );
    $now = time;
    ok( !$status, 'real tests: maintain-cache update succeeded' );
    diag( 'maintain-cache update runtime: '.($now-$then).' seconds' );

    #############################################################################
    # simulate another pulse clone
    #
    $then = time;
    $status = system( 'git', 'clone', '--no-checkout', $repo, $pulse_dest2 );
    $now = time;
    ok( !$status, 'real tests: second git clone succeeded' );
    diag( 'second clone runtime: '.($now-$then).' seconds' );
    system( 'du', '-shx', $pulse_dest2 );

    return;
}

sub run
{
    ok(-x $SCRIPT) || return;

    run_fake_tests();

    SKIP: {
        skip( "export PULSESERVER_GIT_REAL_TESTS=1 if you want to run real, slow, unstable tests", 4 )
            unless $ENV{ PULSESERVER_GIT_REAL_TESTS };
        run_real_tests();
    };

    return;
}

run() if !caller;
1;

