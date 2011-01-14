import "*"

# Stub for private_sshkeys module; put your real ssh keys into a
# module under the `private' directory
define trusted_authorized_keys($user) {
    warning("There are no trusted authorized keys declared.  If you want passwordless ssh access to your test machines, add your ssh keys to the `private_sshkeys' puppet module")

    # Example implementation:
    # Ssh_authorized_key {
    #        ensure  =>  present,
    #        type    =>  "ssh-rsa",
    #        user    =>  $user,
    # }
    # ssh_authorized_key {
    #    "bob@example.com for $user":
    #        key     =>  "AAAAB0BzaB0yc0BAAAABBwAAABBAqBl0kBm0bBxBBBr/t0BquBeBmBydBBsB00B0Ad0imBlA/BkBBBaiB+B0rBgBmBB0jBj+BaB000BBB0tvBz0BnmBBBu/m0BdBBBjp0000BiB/Br0ABB0BB0Bvx/qB0BmBB0r00BBBujsBmlBxaBry0BihB00zbB0Bg0djtfAg0Ba/0B0BieBBnB0hBprBlBBryzh0BiB0BBk0BB0BBB0BoyBBBB0j00ixz0izhqiBBi+0zhwrfBumxB+BBrnBBBB0BjeB0BBdxB0sBmBhni0BaqmBaBxvyB0kAixbeBBBcoBBABjBBBfBBB0n0drt0BsBzwBfBB+ius+BBhA0n0eBBB==";
    #    "jim@example.com for $user":
    #        key     =>  "AAAAC1CzaC1yc1CAAAACCwAAACCAyjCClCyr1CCvCCjuCk1kqC1vCCa1C11C1ykChCw1tCtCCCC1vCCyrmC1AiaCrCaCuCCsh1adsoCjwxi1CCCCCC11kCCo1C1CyChC+1CuixyCCCCrCCCaCC11aCnClCCfCacCCCCClCwk1bpCC1wbpC1yr/b11rnCz111eC1cscCeCC1CC11lCCtCdCCCssh1s1CCCCCl1iqCChCCCCaCpCihqrCrbC1CCngo1muCCfCCeCuCk1/fCCC+CwCCv1iqmC1CdCbmCfCqeCCgCfh1CC1Cz1CjCCC1CCCCCeC1CxgCohCC11C1qjeCCwCC1iCCwCviiy+hugACCuCC1lCzCC==";
    # }
}

define dropbox_authorized_keys($user) {
    warning("There are no dropbox authorized keys declared.  If you want your test machines to have passwordless ssh access to some servers (e.g. to upload files), add the test machine ssh keys to the `private_sshkeys' puppet module")

    # Example implementation is similar as for trusted_authorized_keys but inverted;
    # here, instead of the public keys of the users who adminster your test machines,
    # you put the public keys of the test machines themselves, so that they can e.g.
    # move files between each other with `scp'
}

