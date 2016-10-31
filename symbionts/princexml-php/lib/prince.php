<?php

// Prince - PHP interface
// Copyright 2005-2016 YesLogic Pty. Ltd.
// http://www.princexml.com

class Prince
{
    private $exePath;
    private $styleSheets;
    private $scripts;
    private $fileAttachments;
    private $licenseFile;
    private $licenseKey;
    private $inputType;
    private $javascript;
    private $baseURL;
    private $doXInclude;
    private $httpUser;
    private $httpPassword;
    private $httpProxy;
    private $httpTimeout;
    private $insecure;
    private $logFile;
    private $fileRoot;
    private $embedFonts;
    private $subsetFonts;
    private $noArtificialFonts;
    private $compress;
    private $pdfTitle;
    private $pdfSubject;
    private $pdfAuthor;
    private $pdfKeywords;
    private $pdfCreator;
    private $authMethod;
    private $authUser;
    private $authPassword;
    private $authServer;
    private $authScheme;
    private $noAuthPreemptive;
    private $pageSize;
    private $pageMargin;
    private $encrypt;
    private $encryptInfo;
    private $options;

    public function __construct($exePath)
    {
        $this->exePath = $exePath;
        $this->styleSheets = '';
        $this->scripts = '';
        $this->fileAttachments = '';
        $this->licenseFile = '';
        $this->licenseKey = '';
        $this->inputType = 'auto';
        $this->javascript = false;
        $this->baseURL = '';
        $this->doXInclude = true;
        $this->httpUser = '';
        $this->httpPassword = '';
        $this->httpProxy = '';
        $this->httpTimeout = '';
        $this->insecure = false;
        $this->logFile = '';
        $this->fileRoot = '';
        $this->embedFonts = true;
        $this->subsetFonts = true;
        $this->noArtificialFonts = false;
        $this->compress = true;
        $this->pdfTitle = '';
        $this->pdfSubject = '';
        $this->pdfAuthor = '';
        $this->pdfKeywords = '';
        $this->pdfCreator = '';
        $this->authMethod = '';
        $this->authUser = '';
        $this->authPassword = '';
        $this->authServer = '';
        $this->authScheme = '';
        $this->noAuthPreemptive = false;
        $this->pageSize = '';
        $this->pageMargin = '';
        $this->encrypt = false;
        $this->encryptInfo = '';
        $this->options = '';
    }

    // Add a CSS style sheet that will be applied to each document.
    // cssPath: The filename of the CSS style sheet.
    public function addStyleSheet($cssPath)
    {
        $this->styleSheets .= '-s "' . $cssPath . '" ';
    }

    // Clear all of the CSS style sheets.
    public function clearStyleSheets()
    {
        $this->styleSheets = '';
    }

    // Add a JavaScript script that will be run before conversion.
    // jsPath: The filename of the script.
    public function addScript($jsPath)
    {
        $this->scripts .= '--script "' . $jsPath . '" ';
    }

    // Clear all of the scripts.
    public function clearScripts()
    {
        $this->scripts = '';
    }
    
    //Add a file attachment that will be attached to the PDF file
    //filePath: The filename of the file attachment.
    public function addFileAttachment($filePath)
    {
        $this->fileAttachments .= '--attach=' . '"' . $filePath .  '" ';
    }
    
    //Clear all of the file attachments.
    public function clearFileAttachments()
    {
        $this->fileAttachments = '';
    }
    
    //Specify the license file.
    //file: The filename of the license file.
    public function setLicenseFile($file)
    {
        $this->licenseFile = $file;
    }
    
    //Specify the license key.
    //key: The license key
    public function setLicenseKey($key)
    {
        $this->licenseKey = $key;
    }
    
    //Specify the input type of the document.
    //inputType: Can take a value of : "xml", "html" or "auto".
    public function setInputType($inputType)
    {
        $this->inputType = $inputType;
    }

    // Specify whether JavaScript found in documents should be run. 
    // js: True if document scripts should be run.
    public function setJavaScript($js)
    {
        $this->javascript = $js;
    }

    // Specify whether documents should be parsed as HTML or XML/XHTML.
    // html: True if all documents should be treated as HTML.
    public function setHTML($html)
    {
        if($html)
        {
                $this->inputType = "html";
        }
        else
        {
                $this->inputType = "xml";
        }
    }

    // Specify a file that Prince should use to log error/warning messages.
    // logFile: The filename that Prince should use to log error/warning
    //      messages, or '' to disable logging.
    public function setLog($logFile)
    {
        $this->logFile = $logFile;
    }

    // Specify the base URL of the input document.
    // baseURL: The base URL or path of the input document, or ''.
    public function setBaseURL($baseURL)
    {
        $this->baseURL = $baseURL;
    }

    // Specify whether XML Inclusions (XInclude) processing should be applied
    // to input documents. XInclude processing will be performed by default
    // unless explicitly disabled.
    // xinclude: False to disable XInclude processing.
    public function setXInclude($xinclude)
    {
        $this->doXInclude = $xinclude;
    }

    // Specify a username to use when fetching remote resources over HTTP.
    // user: The username to use for basic HTTP authentication.
    public function setHttpUser($user)
    {
        $this->httpUser = $this->cmdlineArgEscape($user);
    }
    
    // Specify a password to use when fetching remote resources over HTTP.
    // password: The password to use for basic HTTP authentication.
    public function setHttpPassword($password)
    {
        $this->httpPassword = $this->cmdlineArgEscape($password);
    }
    
    //Specify the URL for the HTTP proxy server, if needed.
    //proxy: The URL for the HTTP proxy server.
    public function setHttpProxy($proxy)
    {
        $this->httpProxy = $proxy;
    }

    //Specify the timeout for HTTP requests.
    //timeout: The HTTP timeout in seconds.
    public function setHttpTimeout($timeout)
    {
        $this->httpTimeout = $timeout;
    }

    //Specify whether to disable SSL verification.
    //insecure: If set to true, SSL verification is disabled. (not recommended)
    public function setInsecure($insecure)
    {
        $this->insecure = $insecure;
    }
    
    //Specify the root directory for absolute filenames. This can be used
    //when converting a local file that uses absolute paths to refer to web
    //resources. For example, /images/logo.jpg can be 
    //rewritten to /usr/share/images/logo.jpg by specifying "/usr/share" as the root.
    //fileRoot: The path to prepend to absolute filenames.
    public function setFileRoot($fileRoot)
    {
        $this->fileRoot = $fileRoot;
    }
        
    // Specify whether fonts should be embedded in the output PDF file. Fonts
    // will be embedded by default unless explicitly disabled.
    // embedFonts: False to disable PDF font embedding.
    public function setEmbedFonts($embedFonts)
    {
        $this->embedFonts = $embedFonts;
    }

    // Specify whether embedded fonts should be subset.
    // Fonts will be subset by default unless explicitly disabled.
    // subsetFonts: False to disable PDF font subsetting.
    public function setSubsetFonts($subsetFonts)
    {
        $this->subsetFonts = $subsetFonts;
    }

    // Specify whether artificial bold/italic fonts should be generated if
    // necessary. Artificial fonts are enabled by default.
    // artificialFonts: False to disable artificial bold/italic fonts.
    public function setNoArtificialFonts($noArtificialFonts)
    {
        $this->noArtificialFonts = $noArtificialFonts;
    }

    // Specify whether compression should be applied to the output PDF file.
    // Compression will be applied by default unless explicitly disabled.
    // compress: False to disable PDF compression.
    public function setCompress($compress)
    {
        $this->compress = $compress;
    }

    // Specify the document title for PDF metadata.
    public function setPDFTitle($pdfTitle)
    {
        $this->pdfTitle = $pdfTitle;
    }

    // Specify the document subject for PDF metadata.
    public function setPDFSubject($pdfSubject)
    {
        $this->pdfSubject = $pdfSubject;
    }

    // Specify the document author for PDF metadata.
    public function setPDFAuthor($pdfAuthor)
    {
        $this->pdfAuthor = $pdfAuthor;
    }

    // Specify the document keywords for PDF metadata.
    public function setPDFKeywords($pdfKeywords)
    {
        $this->pdfKeywords = $pdfKeywords;
    }

    // Specify the document creator for PDF metadata.
    public function setPDFCreator($pdfCreator)
    {
        $this->pdfCreator = $pdfCreator;
    }
   
    //Specify HTTP authentication methods. (basic, digest, ntlm, negotiate)
    public function setAuthMethod($authMethod)
    {
    	  if(strcasecmp($authMethod, 'basic') == 0)
    	  {
   	  	$this->authMethod = 'basic';
   	  }
   	  else if(strcasecmp($authMethod, 'digest') == 0)
   	  {
   	  	$this->authMethod = 'digest';
   	  }
   	  else if(strcasecmp($authMethod, 'ntlm') == 0)
   	  {
   	  	$this->authMethod = 'ntlm';
   	  } 
   	  else if(strcasecmp($authMethod, 'negotiate') == 0)
   	  {
   	  	$this->authMethod = 'negotiate';
   	  }
   	  else
   	  {
   	  	$this->authMethod = '';
   	  }
    }
    
    //Specify username for HTTP authentication.
    public function setAuthUser($authUser)
    {
    	 $this->authUser = $authUser;
    }
    
    //Specify password for HTTP authentication.
    public function setAuthPassword($authPassword)
    {
    	 $this->authPassword = $authPassword;
    }
    
    //Only send USER:PASS to this server.
    public function setAuthServer($authServer)
    {
       $this->authServer = $authServer;
    }
    
    //Only send USER:PASS for this scheme. (HTTP, HTTPS)
    public function setAuthScheme($authScheme)
    {
    	  if(strcasecmp($authScheme, 'http') == 0)
    	  {
    	 	$this->authScheme = 'http';
    	  }
    	  else if(strcasecmp($authScheme, 'https') == 0)
    	  {
    	 	$this->authScheme = 'https';
    	  }
    	  else
    	  {
    	 	$this->authScheme = '';
    	  }
    }
    
    //Do not authenticate with named servers until asked.
    public function setNoAuthPreemptive($noAuthPreemptive)
    {
    	 $this->noAuthPreemptive = $noAuthPreemptive;
    }

    //Specify the page size (eg. A4).
    public function setPageSize($pageSize)
    {
    	 $this->pageSize = $pageSize;
    }
    
    //Specify the page margin (eg. 20mm).
    public function setPageMargin($pageMargin)
    {
    	 $this->pageMargin = $pageMargin;
    }

    // Specify whether encryption should be applied to the output PDF file.
    // Encryption will not be applied by default unless explicitly enabled.
    // encrypt: True to enable PDF encryption.
    public function setEncrypt($encrypt)
    {
        $this->encrypt = $encrypt;
    }

    // Set the parameters used for PDF encryption. Calling this method will
    // also enable PDF encryption, equivalent to calling setEncrypt(true).
    // keyBits: The size of the encryption key in bits (must be 40 or 128).
    // userPassword: The user password for the PDF file.
    // ownerPassword: The owner password for the PDF file.
    // disallowPrint: True to disallow printing of the PDF file.
    // disallowModify: True to disallow modification of the PDF file.
    // disallowCopy: True to disallow copying from the PDF file.
    // disallowAnnotate: True to disallow annotation of the PDF file.
      public function setEncryptInfo($keyBits,
                                   $userPassword,
                                   $ownerPassword,
                                   $disallowPrint = false,
                                   $disallowModify = false,
                                   $disallowCopy = false,
                                   $disallowAnnotate = false)
    {
        if ($keyBits != 40 && $keyBits != 128)
        {
            throw new Exception("Invalid value for keyBits: $keyBits" .
                " (must be 40 or 128)");
        }

        $this->encrypt = true;

        $this->encryptInfo =
                ' --key-bits ' . $keyBits .
                ' --user-password="' . $this->cmdlineArgEscape($userPassword) .
                '" --owner-password="' . $this->cmdlineArgEscape($ownerPassword) . '" ';

        if ($disallowPrint)
        {
            $this->encryptInfo .= '--disallow-print ';
        }
            
        if ($disallowModify)
        {
            $this->encryptInfo .= '--disallow-modify ';
        }
            
        if ($disallowCopy)
        {
            $this->encryptInfo .= '--disallow-copy ';
        }
            
        if ($disallowAnnotate)
        {
            $this->encryptInfo .= '--disallow-annotate ';
        }
    }
    
  
    //Set other options.
    public function setOptions($options)
    {
    	  $this->options = $options;
    }
	

    // Convert an XML or HTML file to a PDF file.
    // The name of the output PDF file will be the same as the name of the
    // input file but with an extension of ".pdf".
    // xmlPath: The filename of the input XML or HTML document.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_file($xmlPath, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=normal ';
        $pathAndArgs .= '"' . $xmlPath . '"';
   
        return $this->convert_internal_file_to_file($pathAndArgs, $msgs, $dats);

    }
    
    // Convert an XML or HTML file to a PDF file.
    // xmlPath: The filename of the input XML or HTML document.
    // pdfPath: The filename of the output PDF file.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_file_to_file($xmlPath, $pdfPath, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=normal ';
        $pathAndArgs .= '"' . $xmlPath . '" -o "' . $pdfPath . '"';
            
        return $this->convert_internal_file_to_file($pathAndArgs, $msgs, $dats);
    }
    
    //Convert multiple XML or HTML files to a PDF file.
    // xmlPaths: An array of the input XML or HTML documents.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_multiple_files($xmlPaths, $pdfPath, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=normal ';
        
        foreach($xmlPaths as $xmlPath)
        {
                $pathAndArgs .= '"' . $xmlPath . '" ';
        }
        $pathAndArgs .= '-o "' . $pdfPath . '"';
  
         return $this->convert_internal_file_to_file($pathAndArgs, $msgs, $dats);
    }
    
    // Convert multiple XML or HTML files to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
    // xmlPaths: An array of the input XML or HTML documents.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_multiple_files_to_passthru($xmlPaths, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=buffered ';
        
        foreach($xmlPaths as $xmlPath)
        {
                $pathAndArgs .= '"' . $xmlPath . '" ';
        }
        $pathAndArgs .= '-o -';
        
         return $this->convert_internal_file_to_passthru($pathAndArgs, $msgs, $dats);
    }
    
    // Convert an XML or HTML file to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
    // xmlPath: The filename of the input XML or HTML document.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_file_to_passthru($xmlPath, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=buffered "' . $xmlPath . '" -o -';
            
        return $this->convert_internal_file_to_passthru($pathAndArgs, $msgs, $dats);
    }
    
    // Convert an XML or HTML string to a PDF file, which will be passed
    // through to the output buffer of the current PHP page.
    // xmlString: A string containing an XML or HTML document.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_string_to_passthru($xmlString, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=buffered -';
            
        return $this->convert_internal_string_to_passthru($pathAndArgs, $xmlString, $msgs, $dats);
    }
    
    // Convert an XML or HTML string to a PDF file.
    // xmlString: A string containing an XML or HTML document.
    // pdfPath: The filename of the output PDF file.
    // msgs: An optional array in which to return error and warning messages.
    // dats: An optional array in which to return data messages.
    // Returns true if a PDF file was generated successfully.
    public function convert_string_to_file($xmlString, $pdfPath, &$msgs = array(), &$dats = array())
    {
        $pathAndArgs = $this->getCommandLine();
        $pathAndArgs .= '--structured-log=normal ';
        $pathAndArgs .= ' - -o "' . $pdfPath . '"';
            
        return $this->convert_internal_string_to_file($pathAndArgs, $xmlString, $msgs, $dats);
    }

    private function getCommandLine()
    {
        $cmdline = '"' . $this->exePath . '" ' . $this->styleSheets . $this->scripts . $this->fileAttachments;

        if (strcasecmp($this->inputType, 'auto') == 0)
        {
        }
        else
        {
                $cmdline .=  '-i "' . $this->inputType . '" ';
        }

        if ($this->javascript)
        {
            $cmdline .= '--javascript ';
        }

        if ($this->baseURL != '')
        {
            $cmdline .= '--baseurl="' . $this->baseURL . '" ';
        }

        if ($this->doXInclude == false)
        {
            $cmdline .= '--no-xinclude ';
        }

        if ($this->httpUser != '')
        {
            $cmdline .= '--http-user="' . $this->httpUser . '" ';
        }

        if ($this->httpPassword != '')
        {
            $cmdline .= '--http-password="' . $this->httpPassword . '" ';
        }
        
        if($this->httpProxy != '')
        {
                $cmdline .= '--http-proxy="' . $this->httpProxy . '" ';
        }
        
        if($this->httpTimeout != '')
        {
                $cmdline .= '--http-timeout="' . $this->httpTimeout . '" ';
        }
        
        if($this->insecure)
        {
                $cmdline .= '--insecure ';
        }

        if ($this->logFile != '')
        {
            $cmdline .= '--log="' . $this->logFile . '" ';
        }

        if($this->fileRoot != '')
        {
                 $cmdline .= '--fileroot="' . $this->fileRoot . '" ';
        }
        
        if($this->licenseFile != '')
        {
                $cmdline .= '--license-file="' . $this->licenseFile . '" ';
        }
        
        if($this->licenseKey != '')
        {
                $cmdline .= '--license-key="' . $this->licenseKey . '" ';
        }
        
        if ($this->embedFonts == false)
        {
            $cmdline .= '--no-embed-fonts ';
        }

        if ($this->subsetFonts == false)
        {
            $cmdline .= '--no-subset-fonts ';
        }

        if ($this->noArtificialFonts == true)
        {
            $cmdline .= '--no-artificial-fonts ';
        }
        
        if($this->authMethod != '')
        {
        	$cmdline .=  '--auth-method="' . $this->cmdlineArgEscape($this->authMethod) . '" ';
        }
        
        if($this->authUser != '')
        {
        	$cmdline .= '--auth-user="' . $this->cmdlineArgEscape($this->authUser) . '" ';
        }
        
        if($this->authPassword != '')
        {
        	$cmdline .= '--auth-password="' . $this->cmdlineArgEscape($this->authPassword) . '" ';
        }
        
        if($this->authServer != '')
        {
        	$cmdline .= '--auth-server="' . $this->cmdlineArgEscape($this->authServer) . '" ';
        }
        
        if($this->authScheme != '')
        {
        	$cmdline .= '--auth-scheme="' . $this->cmdlineArgEscape($this->authScheme) . '" ';
        }
        
        if($this->noAuthPreemptive)
        {
        	$cmdline .= '--no-auth-preemptive ';
        }
        
        if($this->pageSize != '')
        {
        	$cmdline .= '--page-size="' . $this->cmdlineArgEscape($this->pageSize) . '" ';
        }
        
        if($this->pageMargin != '')
        {
        	$cmdline .= '--page-margin="' . $this->cmdlineArgEscape($this->pageMargin) . '" ';
        }
        
        if ($this->compress == false)
        {
            $cmdline .= '--no-compress ';
        }

        if ($this->pdfTitle != '')
        {
            $cmdline .= '--pdf-title="' . $this->cmdlineArgEscape($this->pdfTitle) . '" ';
        }

        if ($this->pdfSubject != '')
        {
            $cmdline .= '--pdf-subject="' . $this->cmdlineArgEscape($this->pdfSubject) . '" ';
        }

        if ($this->pdfAuthor != '')
        {
            $cmdline .= '--pdf-author="' . $this->cmdlineArgEscape($this->pdfAuthor) . '" ';
        }

        if ($this->pdfKeywords != '')
        {
            $cmdline .= '--pdf-keywords="' . $this->cmdlineArgEscape($this->pdfKeywords) . '" ';
        }

        if ($this->pdfCreator != '')
        {
            $cmdline .= '--pdf-creator="' . $this->cmdlineArgEscape($this->pdfCreator) . '" ';
        }

        if ($this->encrypt)
        {
            $cmdline .= '--encrypt ' . $this->encryptInfo;
        }
        
        if($this->options != '')
        {
        	$cmdline .= $this->cmdlineArgEscape($this->options) . ' ';
        }

        return $cmdline;
    }

    private function convert_internal_file_to_file($pathAndArgs, &$msgs, &$dats)
    {
        $descriptorspec = array(
                                0 => array("pipe", "r"),
                                1 => array("pipe", "w"),
                                2 => array("pipe", "w")
                                );
        
        $process = proc_open($pathAndArgs, $descriptorspec, $pipes, NULL, NULL, array('bypass_shell' => TRUE));
        
        if (is_resource($process))
        {
            $result = $this->readMessages($pipes[2], $msgs, $dats);

            fclose($pipes[0]);
            fclose($pipes[1]);
            fclose($pipes[2]);
            
            proc_close($process);

            return ($result == 'success');
        }
        else
        {
            throw new Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_string_to_file($pathAndArgs, $xmlString, &$msgs, &$dats)
    {
        $descriptorspec = array(
                            0 => array("pipe", "r"),
                            1 => array("pipe", "w"),
                            2 => array("pipe", "w")
                            );
        
        $process = proc_open($pathAndArgs, $descriptorspec, $pipes, NULL, NULL, array('bypass_shell' => TRUE));
        
        if (is_resource($process))
        {
            fwrite($pipes[0], $xmlString);
            fclose($pipes[0]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs, $dats);
            
            fclose($pipes[2]);
        
            proc_close($process);

            return ($result == 'success');
        }
        else
        {
            throw new Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_file_to_passthru($pathAndArgs, &$msgs, &$dats)
    {
        $descriptorspec = array(
                            0 => array("pipe", "r"),
                            1 => array("pipe", "w"),
                            2 => array("pipe", "w")
                            );
        
        $process = proc_open($pathAndArgs, $descriptorspec, $pipes, NULL, NULL, array('bypass_shell' => TRUE));
        
        if (is_resource($process))
        {
            fclose($pipes[0]);
            fpassthru($pipes[1]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs, $dats);
            
            fclose($pipes[2]);
        
            proc_close($process);

            return ($result == 'success');
        }
        else
        {
            throw new Exception("Failed to execute $pathAndArgs");
        }
    }

    private function convert_internal_string_to_passthru($pathAndArgs, $xmlString, &$msgs, &$dats)
    {
        $descriptorspec = array(
                            0 => array("pipe", "r"),
                            1 => array("pipe", "w"),
                            2 => array("pipe", "w")
                            );
        
        $process = proc_open($pathAndArgs, $descriptorspec, $pipes, NULL, NULL, array('bypass_shell' => TRUE));
        
        if (is_resource($process))
        {
            fwrite($pipes[0], $xmlString);
            fclose($pipes[0]);
            fpassthru($pipes[1]);
            fclose($pipes[1]);

            $result = $this->readMessages($pipes[2], $msgs, $dats);
            
            fclose($pipes[2]);
        
            proc_close($process);

            return ($result == 'success');
        }
        else
        {
            throw new Exception("Failed to execute $pathAndArgs");
        }
    }

    private function readMessages($pipe, &$msgs, &$dats)
    {
        while (!feof($pipe))
        {
            $line = fgets($pipe);
            
            if ($line != false)
            {
                $msgtag = substr($line, 0, 4);
                $msgbody = rtrim(substr($line, 4));
                
                if ($msgtag == 'fin|')
                {
                    return $msgbody;
                }
                else if ($msgtag == 'msg|')
                {
                    $msg = explode('|', $msgbody, 4);

                    // $msg[0] = 'err' | 'wrn' | 'inf'
                    // $msg[1] = filename / line number
                    // $msg[2] = message text, trailing newline stripped

                    $msgs[] = $msg;
                }
		else if ($msgtag == 'dat|')
		{
		    $dat = explode('|', $msgbody, 2);

		    $dats[] = $dat;
		}
                else
                {
                    // ignore other messages
                }
            }
        }
        
        return '';
    }
    
    private function cmdlineArgEscape($argStr)
    {
        return $this->cmdlineArgEscape2($this->cmdlineArgEscape1($argStr));
    }
        
    //In the input string $argStr, a double quote with zero or more preceding backslash(es)
    //will be replaced with: n*backslash + doublequote => (2*n+1)*backslash + doublequote
    private function cmdlineArgEscape1($argStr)
    {
        //chr(34) is character double quote ( " ), chr(92) is character backslash ( \ ).
        $len = strlen($argStr);
        
        $outputStr = '';
        $numSlashes = 0;
        $subStrStart = 0;
        
        for($i = 0; $i < $len; $i++)
        {
            if($argStr[$i] == chr(34))
            {
                $numSlashes = 0;
                $j = $i - 1;
                while($j >= 0)
                {
                    if($argStr[$j] == chr(92))
                    {
                        $numSlashes += 1;
                        $j -= 1;
                    }
                    else
                    {
                        break;
                    }
                }
                
                $outputStr .= substr($argStr, $subStrStart, ($i - $numSlashes - $subStrStart));
                
                for($k = 0; $k < $numSlashes; $k++)
                {
                    $outputStr .= chr(92) . chr(92);
                }
                $outputStr  .= chr(92) . chr(34);
                
                $subStrStart = $i + 1;
            }
        }
        $outputStr .= substr($argStr, $subStrStart, ($i - $subStrStart));
        
        return $outputStr;
    }
        
    //Double the number of trailing backslash(es):      n*trailing backslash => (2*n)*trailing backslash.
    private function cmdlineArgEscape2($argStr)
    {
        //chr(92) is character backslash ( \ ).
        $len = strlen($argStr);
        
        $numTrailingSlashes = 0;
        for($i = ($len - 1); $i  >= 0; $i--)
        {
            if($argStr[$i] == chr(92))
            {
                $numTrailingSlashes += 1;
            }
            else
            {
                break;
            }
        }
        
        while($numTrailingSlashes > 0)
        {
            $argStr .= chr(92);
            $numTrailingSlashes -= 1;
        }
        
        return $argStr;
    }
}
?>
