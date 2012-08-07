class ccache::linux inherits ccache::unix
{
    package { "ccache":
        ensure      =>  present,
    }

    if $ccache::user {
        ccache::link {
                "gcc":     command => "gcc";
                "g++":     command => "g++";
                "cc":      command => "cc";
                "c++":     command => "c++";
        }
    }
}

