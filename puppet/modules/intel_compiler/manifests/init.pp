class intel_compiler {
    case $operatingsystem {
        # Add more as implemented...
        Ubuntu:     { include intel_compiler::linux }
    }
}

