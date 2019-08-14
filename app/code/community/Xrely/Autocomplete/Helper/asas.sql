DELETE  FROM `core_config_data` WHERE `path` LIKE '%xrely_autocomplete%';
DROP TABLE xrely_autocomplete_settings;
DELETE  FROM `core_resource` WHERE `code` LIKE 'xrely_autocomplete_setup';