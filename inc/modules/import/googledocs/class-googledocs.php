<?php

/**
 * @author  Pressbooks <code@pressbooks.com>
 * @license GPLv3 (or any later version)
 */

namespace Pressbooks\Modules\Import\GoogleDocs;

use Pressbooks\Modules\Import\Import;
use Pressbooks\Modules\Import\Ooxml\Docx;

class GoogleDocs extends Import {

    const TYPE_OF = 'googledocs';
    
    /**
     * @var \Google_Client
     */
    protected $client;

    /**
     * @var string
     */
    protected $tempDir;

    /**
     * Constructor
     */
    public function __construct() {
        if (!function_exists('media_handle_sideload')) {
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
        }

        $this->tempDir = \Pressbooks\Utility\create_tmp_file();
        unlink($this->tempDir);
        mkdir($this->tempDir);
    }

    /**
     * @param array $upload
     * @return bool
     */
    public function setCurrentImportOption(array $upload) {
        try {
            // Initialize Google Client
            $this->client = new \Google_Client();
            $this->client->setApplicationName('Pressbooks');
            $this->client->setScopes([\Google_Service_Drive::DRIVE_READONLY]);
            
            // Get credentials from WordPress options
            $credentials = get_option('pb_google_docs_credentials');
            if (empty($credentials)) {
                throw new \Exception(__('Google Docs credentials not found. Please configure them in the settings.', 'pressbooks'));
            }
            
            $this->client->setAuthConfig(json_decode($credentials, true));

            // Extract document ID from URL
            $docId = $this->extractDocId($upload['url']);
            if (!$docId) {
                throw new \Exception(__('Invalid Google Docs URL.', 'pressbooks'));
            }

            // Initialize Drive service
            $service = new \Google_Service_Drive($this->client);
            
            // Get file metadata
            $file = $service->files->get($docId, ['fields' => 'name']);
            
            // Export as DOCX
            $content = $service->files->export($docId, 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', ['alt' => 'media']);
            
            // Save to temporary file
            $tempFile = $this->tempDir . '/document.docx';
            file_put_contents($tempFile, $content->getBody()->getContents());
            
            // Use existing DOCX importer
            $docxImporter = new Docx();
            return $docxImporter->setCurrentImportOption([
                'file' => $tempFile,
                'type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
            ]);

        } catch (\Exception $e) {
            $_SESSION['pb_errors'][] = $e->getMessage();
            return false;
        }
    }

    /**
     * @param array $current_import
     * @return bool
     */
    public function import(array $current_import) {
        try {
            // Use existing DOCX importer
            $docxImporter = new Docx();
            $result = $docxImporter->import($current_import);
            
            // Cleanup
            $this->cleanup();
            
            return $result;
        } catch (\Exception $e) {
            $this->cleanup();
            return false;
        }
    }

    /**
     * Extract Google Doc ID from URL
     * 
     * @param string $url
     * @return string|false
     */
    protected function extractDocId($url) {
        preg_match('/\/document\/d\/([a-zA-Z0-9-_]+)/', $url, $matches);
        return $matches[1] ?? false;
    }

    /**
     * Cleanup temporary files
     */
    protected function cleanup() {
        if (is_dir($this->tempDir)) {
            array_map('unlink', glob("$this->tempDir/*.*"));
            rmdir($this->tempDir);
        }
    }

    /**
     * Destructor
     */
    public function __destruct() {
        $this->cleanup();
    }
} 