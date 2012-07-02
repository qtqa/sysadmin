class pulseagent::linux inherits pulseagent::unix {
    baselayout::startup { "pulseagent":
        path    =>  "/home/$testuser/pulse-agent.sh",
        require =>  File["/home/$testuser/pulse-agent.sh"],
        user    =>  $testuser,
    }
}

