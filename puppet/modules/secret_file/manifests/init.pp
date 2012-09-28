# Fetch a file which is too secret to be put under any source control.
define secret_file($source) {
    if $input == "" {
        warning("HTTP input URL is not defined, it needs to be set for secret_file to work")
    } else {
        $download_url = "$input/semisecure/$source"

        $fetch = $operatingsystem ? {
            Darwin  =>  "/usr/bin/curl -o $name $download_url",
            Solaris =>  "/opt/csw/bin/wget -O $name $download_url",
            windows => $::architecture ? {
                x64 => "\"c:\\Program Files (x86)\\Git\\bin\\curl.exe\" -o $name $download_url",
                default => "\"c:\\Program Files\\Git\\bin\\curl.exe\" -o $name $download_url",
            },
            default =>  "/usr/bin/wget -O $name $download_url",
        }

        exec { "fetch secret file $source":
            command =>  $fetch,
            creates =>  $name,
        }
    }
}

