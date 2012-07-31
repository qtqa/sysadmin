class pulseagent::mac inherits pulseagent::unix {
    $user = $pulseagent::user
    baselayout::startup { "pulseagent":
        path    =>  "/Users/$user/pulse-agent.sh",
        require =>  File["/Users/$user/pulse-agent.sh"],
        user    =>  $user,
    }
}

