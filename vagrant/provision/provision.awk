###
# Modifying Yii2's files for initialize Vagrant VM
#
# @author HA3IK <golubha3ik@gmail.com>
# @version 1.0.0

BEGIN {
    print "AWK BEGINs its work:"
    IGNORECASE = 1
    # Correct IP - wildcard last octet
    match(ip, /(([0-9]+\.)+)/, arr)
    ip = arr[1] "*"
}
BEGINFILE {
    print "- Work with: " FILENAME
}
END {
    print "AWK ENDs its work."
}
