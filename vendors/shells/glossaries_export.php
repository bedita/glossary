<?php
/*-----8<--------------------------------------------------------------------
 *
 * Glossaries BEdita module
 *
 * Copyright 2025 Chialab Srl
 *
 *------------------------------------------------------------------->8-----
 */

require_once APP . DS . 'vendors' . DS . 'shells' . DS . 'bedita_base.php';
BeLib::getObject('BeConfigure')->initConfig();

/**
 * Glossaries Export shell script.
 */
class GlossariesExportShell extends BeditaBaseShell
{
    /**
     * Models to be used.
     *
     * @var array
     */
    public $uses = ['BEObject', 'DefinitionGroup', 'DefinitionTerm'];

    /**
     * Map of paths, filenames, file pointers, etc..
     *
     * @var array
     */
    protected $map = [
        'baseUrl' => '',
        'counters' => [
            'audios' => 0,
            'images' => 0,
            'videos' => 0,
            'glossaries' => 0,
            'terms' => 0,
            'media' => 0,
            'errors' => 0,
            'posters' => 0,
            'attachments' => 0,
        ],
        'csv' => [
            'delimiter' => ';',
            'enclosure' => '"',
            'escape' => '"',
            'firstRowMedia' => true,
            'firstRowTerms' => true,
        ],
        'filenames' => [
            'media' => 'media.csv',
            'glossaries' => 'glossaries.csv',
            'terms' => 'terms.csv',
            'zip' => 'glossaries-export.zip',
        ],
        'filepointers' => [
            'media' => null,
            'glossaries' => null,
            'terms' => null,
        ],
        'limits' => [
            'glossaries' => 500,
        ],
        'paths' => [
            'media' => '/tmp/glossaries-export-media',
        ],
        'verbose' => false,
    ];

    /**
     * Cache for terms titles.
     *
     * @var array
     */
    protected $termsTitlesCache = [];

    /**
     * ZipArchive instance.
     *
     * @var ZipArchive|null
     */
    protected $zip = null;

    /**
     * Display help.
     *
     * @return void
     */
    public function help()
    {
        $this->out('Export glossaries, related definition groups and terms into a zip file');
        $this->out(' ');
        $this->out('  glossaries_export [--id | -i <id>] [--limit | -l <number>] [--verbose | -v]');
        $this->out(' ');
        $this->out('  Arguments:');
        $this->out('    help \t show this help');
        $this->out(' ');
        $this->out('  Options:');
        $this->out("    --help | -h show this help");
        $this->out("    --id | -i <id> export only the glossary with the given ID");
        $this->out("    --limit | -l <number> limit the number of glossaries to export, default is 500");
        $this->out("    --verbose | -v verbose mode");
        $this->out(' ');
    }

    /**
     * @inheritDoc
     */
    public function main()
    {
        $this->out('Glossaries Export');
        $this->hr();
        $this->out('This will export glossaries, related definition groups and terms into a zip file.');
        $this->out('The zip file will contain CSV files (glossaries.csv, terms.csv, media.csv).');
        $this->hr();
        $paramsKeys = array_keys($this->params);
        $this->map['baseUrl'] = (string)Configure::read('mediaUrl');
        $this->out(sprintf('Media URL: %s', $this->map['baseUrl']));
        $this->map['verbose'] = in_array('v', $paramsKeys) || in_array('-verbose', $paramsKeys);
        $this->out(sprintf('Verbose mode is %s', $this->map['verbose'] ? 'on' : 'off'));
        $this->out(sprintf('Limit: %d', $this->limit()));
        $glossaryId = null;
        if (in_array('i', $paramsKeys)) {
            $glossaryId = (int)$this->params['i'];
        } elseif (in_array('-id', $paramsKeys)) {
            $glossaryId = (int)$this->params['-id'];
        }
        if (!empty($glossaryId)) {
            $this->out(sprintf('Exporting only glossary with ID: %d', $glossaryId));
        }
        $this->hr();
        $this->out();
        $this->out('Starting export...');
        $this->hr();
        try {
            $this->map['paths']['media'] = '/tmp/glossaries-export-media-' . date('Ymd-His');
            $this->map['filenames']['zip'] = sprintf('glossaries-%s-export.zip', date('Ymd-His'));
            $this->map['filepointers']['media'] = fopen($this->map['filenames']['media'], 'w');
            $this->map['filepointers']['glossaries'] = fopen($this->map['filenames']['glossaries'], 'w');
            $this->map['filepointers']['terms'] = fopen($this->map['filenames']['terms'], 'w');
            $this->zip = new ZipArchive();
            $this->zip->open($this->map['filenames']['zip'], ZipArchive::CREATE);
            $firstRow = true;
            $glossaries = $this->glossaries($glossaryId);
            $glossariesIds = Set::classicExtract($glossaries, '{n}.id');
            $streams = $this->fetchPosterStreams($glossariesIds);
            $streamsMap = [];
            foreach ($streams as $item) {
                if (empty($item['Stream']['uri'])) {
                    continue;
                }
                $streamsMap[$item['RelatedObject']['id']][] = $item;
            }
            $attachments = $this->fetchAttachments($glossariesIds);
            $attachmentsMap = [];
            foreach ($attachments as $item) {
                $attachmentsMap[$item['RelatedObject']['id']][] = $item;
            }
            foreach ($glossaries as $item) {
                $glossaryId = $item['id'];
                if ($this->map['verbose']) {
                    $prefix = sprintf('(%d/%d)', $this->map['counters']['glossaries'] + 1, count($glossaries));
                    $this->out(
                        sprintf(
                            '%s Processing glossary ID %s (%s)',
                            $prefix,
                            $glossaryId,
                            $item['title']
                        )
                    );
                }
                $row = [
                    'id' => $glossaryId,
                    'nickname' => $item['nickname'],
                    'poster_id' => null,
                    'attach_ids' => null,
                    'title' => self::cf($item['title']),
                    'description' => self::cf($item['description']),
                    'nickname' => $item['nickname'],
                    'status' => $item['status'],
                    'lang' => $item['lang'],
                    'publisher' => $item['publisher'],
                    'rights' => $item['rights'],
                    'license' => $item['license'],
                    'protezione_domini' => Set::classicExtract($item, 'customProperties.protezione_domini'),
                    'permissions' => $this->permissions($glossaryId),
                ];
                $posters = array_key_exists($glossaryId, $streamsMap) ? $streamsMap[$glossaryId] : [];
                if (!empty($posters)) {
                    $row['poster_id'] = $this->processPoster($posters[0]);
                }
                $relatedAttachments = array_key_exists($glossaryId, $attachmentsMap) ? $attachmentsMap[$glossaryId] : [];
                if (!empty($relatedAttachments)) {
                    $attachIds = $this->processAttachments($relatedAttachments);
                    $row['attach_ids'] = implode(',', $attachIds);
                }
                $this->terms($glossaryId);
                $this->fillCsv('glossaries', $row, $firstRow);
                $firstRow = false;
                $this->map['counters']['glossaries']++;
            }
            $firstRow = true;
            if ($this->map['verbose']) {
                $this->out('Adding files to zip archive');
            }
            $this->zip->addFile($this->map['filenames']['terms'], $this->map['filenames']['terms']);
            $this->zip->addFile($this->map['filenames']['media'], $this->map['filenames']['media']);
            $this->zip->addFile($this->map['filenames']['glossaries'], $this->map['filenames']['glossaries']);
            $this->zip->close();
        } catch (Exception $e) {
            $this->error($e->getMessage());
            $this->map['counters']['errors']++;
        } finally {
            fclose($this->map['filepointers']['terms']);
            fclose($this->map['filepointers']['media']);
            fclose($this->map['filepointers']['glossaries']);
            @unlink($this->map['filenames']['terms']);
            @unlink($this->map['filenames']['media']);
            @unlink($this->map['filenames']['glossaries']);
        }
        $this->out();
        $this->hr();
        $this->out('Summary');
        $this->out();
        $this->out(sprintf('Glossaries: %s', $this->map['counters']['glossaries']));
        $this->out(sprintf('Terms: %s', $this->map['counters']['terms']));
        $this->out(
            sprintf(
                'Media: %s (%s audio, %s images, %s videos)',
                $this->map['counters']['media'],
                $this->map['counters']['audios'],
                $this->map['counters']['images'],
                $this->map['counters']['videos']
            )
        );
        $this->out(
            sprintf(
                'Relations: posters: %s, attachments %s',
                $this->map['counters']['posters'],
                $this->map['counters']['attachments']
            )
        );
        $this->out(sprintf('Created: %s', $this->map['filenames']['zip']));
        $this->out(sprintf('Errors: %s', $this->map['counters']['errors']));
        $this->hr();
    }

    /**
     * Clean a field value from new lines and trim it.
     * Also escape characters that could cause issues in CSV format.
     *
     * @param string $val The value to clean
     * @return string The cleaned value
     */
    private static function cf($val)
    {
        if (empty($val)) {
            return $val;
        }
        // Remove newlines and trim
        $cleaned = trim(str_replace(["\r", "\r\n", "\n"], '', $val));

        // replace ’ with '
        $cleaned = str_replace('’', "'", $cleaned);

        // Additional escaping for CSV safety - escape double quotes by doubling them
        return str_replace('"', '""', $cleaned);
    }

    /**
     * Get limit from command line parameters or use default.
     *
     * @return int The limit of glossaries to export
     */
    protected function limit()
    {
        $limit = 500;
        $keys = array_keys($this->params);
        if (in_array('-limit', $keys)) {
            $limit = (int)$this->params['-limit'];
        } elseif (in_array('-l', $keys)) {
            $limit = (int)$this->params['-l'];
        }
        $this->map['limits']['glossaries'] = $limit;

        return $limit;
    }

    /**
     * Fetch glossaries data.
     *
     * @param string|null $glossaryId The glossary ID to fetch (optional)
     * @return array
     */
    protected function glossaries($glossaryId = null)
    {
        $objectTypeId = (string)Configure::read('objectTypes.definition_group.id');
        $conditions = ['object_type_id' => $objectTypeId];
        if (!empty($glossaryId)) {
            $conditions['DefinitionGroup.id'] = $glossaryId;
        }

        return $this->DefinitionGroup->find('all', [
            'limit' => $this->map['limits']['glossaries'],
            'conditions' => $conditions,
            'contain' => [
                'BEObject' => ['ObjectProperty'],
            ],
        ]);
    }

    /**
     * Process attachments
     *
     * @param array $items The items
     * @return array The processed IDs
     */
    protected function processAttachments($items)
    {
        $ids = [];
        foreach ($items as $item) {
            $ids[] = $this->processRelatedMedia($item, 'attach');
        }

        return $ids;
    }

    /**
     * Process a poster item (image or video).
     *
     * @param array $data The media data
     * @return string The media ID
     */
    protected function processPoster($data)
    {
        return $this->processRelatedMedia($data, 'poster');
    }

    /**
     * Process a media item (image or video).
     *
     * @param array $data The media data
     * @param string $relation The relation type (poster or attach)
     * @return string The media ID
     */
    protected function processRelatedMedia($data, $relation)
    {
        if ($this->map['verbose']) {
            $this->out(
                sprintf(
                    '. Processing %s ID %s (%s)',
                    $relation,
                    $data['RelatedObject']['object_id'],
                    $data['Stream']['name']
                )
            );
        }
        $providerUrl = null;
        $provider = null;
        $videoUuid = null;
        if (!empty($data['Video']['provider'])) {
            $provider = $data['Video']['provider'];
            $videoUuid = $data['Video']['video_uid'];
        } else {
            $uri = $data['Stream']['uri'];
            $providerUrl = sprintf('%s%s', $this->map['baseUrl'], str_replace('//', '/', $uri));
            if (strpos($uri, 'http://') === 0 || strpos($uri, 'https://') === 0) {
                $providerUrl = $uri;
            }
        }
        $mediaRow = [
            'id' => $data['RelatedObject']['object_id'],
            'title' => self::cf($data['Object']['title']),
            'description' => self::cf($data['Object']['description']),
            'lang' => $data['Object']['lang'],
            'nickname' => $data['Object']['nickname'],
            'provider_url' => $providerUrl,
            'provider' => $provider,
            'video_uid' => $videoUuid,
        ];
        $this->fillCsv('media', $mediaRow, $this->map['csv']['firstRowMedia']);
        $this->map['csv']['firstRowMedia'] = false;
        $this->map['counters']['media']++;
        if (!empty($data['Video']['provider'])) {
            $this->map['counters']['videos']++;
        } elseif (strpos($data['Stream']['mime_type'], 'image/') === 0) {
            $this->map['counters']['images']++;
        } elseif (strpos($data['Stream']['mime_type'], 'audio/') === 0) {
            $this->map['counters']['audios']++;
        } elseif (strpos($data['Stream']['mime_type'], 'video/') === 0) {
            $this->map['counters']['videos']++;
        }

        return $mediaRow['id'];
    }

    /**
     * Fetch terms for a given object ID.
     *
     * @param string $objectId The object ID
     * @return string JSON encoded array of terms
     */
    public function terms($objectId)
    {
        $terms = [];
        $res = $this->DefinitionGroup->find('first', [
            'conditions' => [
                'DefinitionGroup.id' => $objectId
            ],
            'contain' => [
                'BEObject' => [
                    'RelatedObject.switch = "definition_terms"',
                ],
            ],
        ]);
        $related = $res['RelatedObject'];
        usort($related, function ($a, $b) {
            $lvlA = !empty($a['params']['level']) ? $a['params']['level'] : null;
            $lvlB = !empty($b['params']['level']) ? $b['params']['level'] : null;
            if (!$lvlA && !$lvlB) {
                return $a['priority'] - $b['priority'];
            } elseif (!$lvlA) {
                return 1;
            } elseif (!$lvlB) {
                return -1;
            } else {
                return $a['params']['level'] - $b['params']['level'];
            }
        });

        $termsIds = Set::classicExtract($related, '{n}.object_id');
        $resTerms = $this->DefinitionTerm->find('all', [
            'conditions' => [
                'DefinitionTerm.id' => $termsIds,
            ],
            'contain' => [
                'BEObject' => [
                    'Category',
                    'RelatedObject.switch = "is_equivalent_to"',
                ],
            ],
        ]);
        // map terms by ID to preserve order
        $termsMap = [];
        foreach ($resTerms as $item) {
            $termsMap[$item['id']] = $item;
        }

        $terms = [];
        $this->fillTermsTitlesCache($termsMap, $termsIds);
        foreach ($termsIds as $termId) {
            $item = $termsMap[$termId];
            $terms[] = [
                'id' => $item['id'],
                'nickname' => $item['nickname'],
                'lang' => $item['lang'],
                'glossary_id' => $objectId,
                'poster_id' => null,
                'attach_ids' => null,
                'title' => self::cf($item['title']),
                'description' => self::cf($item['description']),
                'categories' => implode(',', (array)Set::classicExtract($item, 'Category.{n}.label')),
                'equivalents' => $this->termsTitles((array)Set::classicExtract($item, 'RelatedObject.{n}.object_id')),
            ];
            $this->map['counters']['terms']++;
        }
        if ($this->map['verbose']) {
            $this->out(
                sprintf(
                    '. Found %d chronologies terms for glossary ID %s',
                    count($terms),
                    $objectId
                )
            );
        }
        if (empty($termsIds)) {
            return json_encode($terms, JSON_HEX_QUOT);
        }
        // fetch poster images for terms
        $posters = $this->fetchPosterStreams($termsIds);
        if ($this->map['verbose']) {
            $this->out(
                sprintf(
                    '. Found %d posters for terms of glossary ID %s',
                    count($posters),
                    $objectId
                )
            );
        }
        // fetch attachments for terms
        $attachments = $this->fetchAttachments($termsIds);
        $counter = 0;
        $counterPoster = 0;
        $counterAttachments = 0;
        foreach ($terms as $term) {
            $poster = array_filter($posters, function ($item) use ($term) {
                return $item['RelatedObject']['id'] === $term['id'];
            });
            if (!empty($poster)) {
                $poster = reset($poster);
                $term['poster_id'] = $this->processPoster($poster);
                $counterPoster++;
                $this->map['counters']['posters']++;
            }
            $termAttachments = array_filter($attachments, function ($item) use ($term) {
                return $item['RelatedObject']['id'] === $term['id'];
            });
            if (!empty($termAttachments)) {
                $attachIds = $this->processAttachments($termAttachments);
                $term['attach_ids'] = implode(',', $attachIds);
                $counterAttachments += count($termAttachments);
                $this->map['counters']['attachments'] += count($termAttachments);
            }
            $this->fillCsv('terms', $term, $this->map['csv']['firstRowTerms']);
            $this->map['csv']['firstRowTerms'] = false;
            $counter++;
        }
        if ($this->map['verbose']) {
            $this->out(
                sprintf(
                    '. Processed %d terms / %d poster images / %d attachments',
                    $counter,
                    $counterPoster,
                    $counterAttachments
                )
            );
        }

        return json_encode($terms, JSON_HEX_QUOT);
    }

    /**
     * Fetch attachments for given object IDs.
     *
     * @param array $ids The object IDs
     * @return array
     */
    protected function fetchAttachments($ids)
    {
        $audios = $this->fetchAudioAttachments($ids);
        $images = $this->fetchImageAttachments($ids);
        $videos = $this->fetchVideoAttachments($ids);

        return array_merge($audios, $images, $videos);
    }

    protected function fetchAudioAttachments($ids)
    {
        $Streams = ClassRegistry::init('Stream');

        return $Streams->find('all', [
            'fields' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'Object.nickname',
                'Object.title',
                'Object.description',
                'Object.lang',
                'Stream.uri',
                'Stream.name',
                'Stream.mime_type',
                'Stream.file_size',
                'Stream.hash_file',
                'Stream.original_name',
            ],
            'conditions' => [
                'Stream.mime_type LIKE' => 'audio%',
            ],
            'contain' => [],
            'joins' => [
                [
                    'table' => 'object_relations',
                    'alias' => 'RelatedObject',
                    'type' => 'INNER',
                    'conditions' => [
                        'RelatedObject.object_id = Stream.id',
                        'RelatedObject.switch' => 'attach',
                        sprintf('RelatedObject.id IN (%s)', implode(',', $ids)),
                    ],
                ],
                [
                    'table' => 'objects',
                    'alias' => 'Object',
                    'type' => 'INNER',
                    'conditions' => [
                        'Object.id = Stream.id',
                        'Object.status' => 'on',
                    ],
                ],
            ],
            'order' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'RelatedObject.priority ASC',
            ],
        ]);
    }

    protected function fetchImageAttachments($ids)
    {
        $Streams = ClassRegistry::init('Stream');

        return $Streams->find('all', [
            'fields' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'Object.nickname',
                'Object.title',
                'Object.description',
                'Object.lang',
                'Stream.uri',
                'Stream.name',
                'Stream.mime_type',
                'Stream.file_size',
                'Stream.hash_file',
                'Stream.original_name',
            ],
            'conditions' => [
                'Stream.mime_type LIKE' => 'image%',
            ],
            'contain' => [],
            'joins' => [
                [
                    'table' => 'object_relations',
                    'alias' => 'RelatedObject',
                    'type' => 'INNER',
                    'conditions' => [
                        'RelatedObject.object_id = Stream.id',
                        'RelatedObject.switch' => 'attach',
                        sprintf('RelatedObject.id IN (%s)', implode(',', $ids)),
                    ],
                ],
                [
                    'table' => 'objects',
                    'alias' => 'Object',
                    'type' => 'INNER',
                    'conditions' => [
                        'Object.id = Stream.id',
                        'Object.status' => 'on',
                    ],
                ],
            ],
            'order' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'RelatedObject.priority ASC',
            ],
        ]);
    }

    protected function fetchVideoAttachments($ids)
    {
        $Streams = ClassRegistry::init('Stream');

        return $Streams->find('all', [
            'fields' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'Object.nickname',
                'Object.title',
                'Object.description',
                'Object.lang',
                'Stream.uri',
                'Stream.name',
                'Stream.mime_type',
                'Stream.file_size',
                'Stream.hash_file',
                'Stream.original_name',
                'Video.provider',
                'Video.video_uid',
            ],
            'conditions' => [
                'Stream.mime_type LIKE' => 'video%',
            ],
            'contain' => [],
            'joins' => [
                [
                    'table' => 'object_relations',
                    'alias' => 'RelatedObject',
                    'type' => 'INNER',
                    'conditions' => [
                        'RelatedObject.object_id = Stream.id',
                        'RelatedObject.switch' => 'attach',
                        sprintf('RelatedObject.id IN (%s)', implode(',', $ids)),
                    ],
                ],
                [
                    'table' => 'objects',
                    'alias' => 'Object',
                    'type' => 'INNER',
                    'conditions' => [
                        'Object.id = Stream.id',
                        'Object.status' => 'on',
                    ],
                ],
                [
                    'table' => 'videos',
                    'alias' => 'Video',
                    'type' => 'INNER',
                    'conditions' => [
                        'Video.id = Stream.id',
                    ],
                ],
            ],
            'order' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'RelatedObject.priority ASC',
            ],
        ]);
    }

    /**
     * Fetch poster streams for given object IDs.
     *
     * @param array $ids The object IDs
     * @return array
     */
    protected function fetchPosterStreams($ids)
    {
        $Stream = ClassRegistry::init('Stream');

        return $Stream->find('all', [
            'fields' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'Object.nickname',
                'Object.title',
                'Object.description',
                'Object.lang',
                'Stream.uri',
                'Stream.name',
                'Stream.mime_type',
                'Stream.file_size',
                'Stream.hash_file',
                'Stream.original_name',
                'Video.provider',
                'Video.video_uid',
            ],
            'contain' => [],
            'joins' => [
                [
                    'table' => 'object_relations',
                    'alias' => 'RelatedObject',
                    'type' => 'INNER',
                    'conditions' => [
                        'RelatedObject.object_id = Stream.id',
                        'RelatedObject.switch' => 'poster',
                        sprintf('RelatedObject.id IN (%s)', implode(',', $ids)),
                    ],
                ],
                [
                    'table' => 'objects',
                    'alias' => 'Object',
                    'type' => 'INNER',
                    'conditions' => [
                        'Object.id = Stream.id',
                        'Object.status' => 'on',
                    ],
                ],
                [
                    'table' => 'videos',
                    'alias' => 'Video',
                    'type' => 'LEFT',
                    'conditions' => [
                        'Video.id = Stream.id',
                    ],
                ],
            ],
            'order' => [
                'RelatedObject.id',
                'RelatedObject.object_id',
                'RelatedObject.priority ASC',
            ],
        ]);
    }

    /**
     * Fill a CSV file with given data.
     *
     * @param resource $key The file pointer key in the map
     * @param array $data The data to write
     * @param bool $firstRow Whether this is the first row (to write headers)
     * @return void
     */
    protected function fillCsv($key, $data, $firstRow)
    {
        $fp = $this->map['filepointers'][$key];
        if ($firstRow) {
            fputcsv($fp, array_keys($data), $this->map['csv']['delimiter'], $this->map['csv']['enclosure'], $this->map['csv']['escape']);
        }
        fputcsv($fp, $data, $this->map['csv']['delimiter'], $this->map['csv']['enclosure'], $this->map['csv']['escape']);
    }

    /**
     * Get permissions for a given object.
     *
     * @param string $objectId The ID of the object
     * @return string|null JSON encoded array of permissions or null if none
     */
    protected function permissions($objectId)
    {
        $PermissionModel = ClassRegistry::init('Permission');
        $items = $PermissionModel->find('all', [
            'fields' => ['Group.name'],
            'conditions' => [
                'object_id' => $objectId,
                'switch' => 'group',
            ],
            'join' => [
                [
                    'table' => 'groups',
                    'alias' => 'Group',
                    'type' => 'LEFT',
                    'conditions' => [
                        'Group.id = Permission.ugid',
                    ],
                ],
            ],
        ]);
        if (empty($items)) {
            return null;
        }
        $allowed = ['Z_insegnanti', 'Z_insegnanti_universitari', 'Z_studenti', 'Z_studenti_universitari'];
        $permissions = [];
        foreach ($items as $item) {
            if (!in_array($item['Group']['name'], $allowed)) {
                continue;
            }
            $permissions[] = $item['Group']['name'];
        }
        if (empty($permissions)) {
            return null;
        }
        // string like ['Z_insegnanti','Z_studenti']
        $permissions = array_map(function ($name) {
            return sprintf("'%s'", $name);
        }, $permissions);

        return sprintf("%s", '[' . implode(',', $permissions) . ']');
    }

    /**
     * Process equivalents to fill terms titles cache.
     *
     * @param array $termsMap The map of terms
     * @param array $termsIds The term IDs
     * @return void
     */
    private function fillTermsTitlesCache(array &$termsMap, array $termsIds)
    {
        $equivalentsIds = [];
        foreach ($termsIds as $termId) {
            $item = $termsMap[$termId];
            $related = $item['RelatedObject'];
            foreach ($related as $rel) {
                $equivId = $rel['object_id'];
                if (!array_key_exists($equivId, $termsMap) && !in_array($equivId, $equivalentsIds)) {
                    $equivalentsIds[] = $equivId;
                }
            }
        }
        if (empty($equivalentsIds)) {
            return;
        }
        $res = $this->BEObject->find('list', [
            'fields' => ['id', 'title'],
            'conditions' => [
                'object_type_id' => Configure::read('objectTypes.definition_term.id'),
                'id' => $equivalentsIds,
            ],
        ]);
        foreach ($res as $id => $title) {
            $this->termsTitlesCache[$id] = self::cf($title);
        }
    }

    /**
     * Get titles of terms by their IDs.
     *
     * @param array $ids The term IDs
     * @return string Comma-separated titles
     */
    private function termsTitles(array $ids)
    {
        if (empty($ids)) {
            return '';
        }
        $titles = [];
        foreach ($ids as $id) {
            $titles[] = $this->termsTitlesCache[$id];
        }
        $res = implode(', ', $titles);

        return $res;
    }
}
