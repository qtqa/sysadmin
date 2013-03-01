class dependencywalker {
    case $::kernel {
        windows: { include dependencywalker::windows }
    }
}
