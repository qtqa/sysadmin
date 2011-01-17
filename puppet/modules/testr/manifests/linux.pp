class testr::linux {
    # famd is used to watch for new uploads of test results
    service { "fam":
        ensure      =>  running,
        enable      =>  true,
    }
}

