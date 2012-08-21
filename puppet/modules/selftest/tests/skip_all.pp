# if skip_all works as expected, this test should 'succeed' even though there are errors
selftest::skip_all { "test skip": }

selftest::expect { "skipped test": output => "foo bar baz quux" }
