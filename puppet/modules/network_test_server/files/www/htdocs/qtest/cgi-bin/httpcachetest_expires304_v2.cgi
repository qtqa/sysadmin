#!/bin/bash
if [ -n "${HTTP_IF_MODIFIED_SINCE}" ] ; then
    echo "Status: 304"
    echo ""
    exit;
fi

echo "Expires: Mon, 30 Oct 2028 14:19:41 GMT"
echo "Content-type: text/html";
echo ""
echo "Hello World!"
