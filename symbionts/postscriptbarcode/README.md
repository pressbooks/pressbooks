
# Barcode Writer in Pure PostScript

Source: https://github.com/bwipp/postscriptbarcode

## HOWTO

The postscriptbarcode library is huge. We only care about ISBN and CODE128.

To reduce size and code complexity:

 + Download postscriptbarcode source code from Github.
 + Run `make build/standalone/isbn.ps && make build/standalone/code128.ps`
 + Copy the newly created .ps files from `build/standalone` directory into here. 

