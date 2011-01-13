class pulseagent::linux inherits pulseagent::unix {
    startup { "pulseagent":
        command =>  '$HOME/pulse-agent.sh',
        require =>  File["/home/$testuser/pulse-agent.sh"],
        user    =>  $testuser,
    }
}

