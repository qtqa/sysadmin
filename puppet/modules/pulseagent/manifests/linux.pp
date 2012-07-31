class pulseagent::linux inherits pulseagent::unix {
    $user = $pulseagent::user
    baselayout::startup { "pulseagent":
        path    =>  "/home/$user/pulse-agent.sh",
        require =>  File["/home/$user/pulse-agent.sh"],
        user    =>  $user,
    }
}

