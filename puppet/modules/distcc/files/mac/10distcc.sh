# Make ccache use distcc.
# Note, if ccache is not used, this doesn't harm anything, but nor does it
# enable the use of distcc.
CCACHE_PREFIX=distcc
export CCACHE_PREFIX

