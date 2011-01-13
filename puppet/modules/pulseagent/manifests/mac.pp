class pulseagent::mac inherits pulseagent::unix {
    exec { "pulseagent login item":
        name    =>  "/usr/bin/sudo -u $testuser /bin/sh -c \"defaults delete loginwindow AutoLaunchedApplicationDictionary; defaults write loginwindow AutoLaunchedApplicationDictionary -array-add '<dict><key>Hide</key><false/><key>Path</key><string>/Users/$testuser/pulse-agent.command</string></dict>'\""
    }
}

