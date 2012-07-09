class pulseagent::mac inherits pulseagent::unix {
    baselayout::startup { "pulseagent":
        path    =>  "/Users/$testuser/pulse-agent.sh",
        require =>  File["/Users/$testuser/pulse-agent.sh"],
        user    =>  $testuser,
    }
}

