class pulseagent::linux inherits pulseagent::unix {
    startup { "pulseagent":
        command =>  "/home/$testuser/pulse-agent.sh",
        require =>  File["/home/$testuser/pulse-agent.sh"],
        user    =>  $testuser,
    }
}

