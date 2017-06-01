<?php

/**
 * @file
 * Assertions for 'collection' migration.
 */

use Drupal\file\Entity\File;

// Migration counts.
$this->assertTotalCount('collection', 7);
$this->assertSuccessCount('collection', 7);

// Imported content check.
/* @var \Drupal\rdf_entity\RdfInterface $collection */
$collection = $new_collection;
$this->assertEquals('New collection', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'The administrative geography and civil voting area ontology',
  'CIPA e-Delivery',
], $collection->field_ar_affiliates);
$this->assertReferences(['eProcurement'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $collection->get('field_ar_owner'));
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('Abstract for a new collection', $collection->field_ar_abstract->value);
$this->assertEquals('content_editor', $collection->field_ar_abstract->format);

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with erroneous items');
$this->assertEquals('Collection with erroneous items', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertTrue($collection->get('field_spatial_coverage')->isEmpty());
$this->assertReferences(['Defence'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertTrue($collection->get('field_ar_abstract')->isEmpty());
$this->assertTrue($collection->get('field_ar_description')->isEmpty());

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Project');
$this->assertEquals('Collection from Project', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1323340905), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1462873423), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'Asset Description Metadata Schema (ADMS)',
], $collection->get('field_ar_affiliates'));
$this->assertTrue($collection->get('field_spatial_coverage')->isEmpty());
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertReferences([
  'Dark Side of The Force',
], $collection->get('field_ar_owner'));
$this->assertReferences(['contact@semic.eu'], $collection->get('field_ar_contact_information'));
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('The Asset Description Metadata Schema (ADMS) is a metadata vocabulary to describe semantic interoperability assets.', $collection->get('field_ar_abstract')->value);
$this->assertStringEndsWith("Government Metadata</a></div>\r\n\t</li>\r\n</ul>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$this->assertTrue($collection->get('field_ar_access_url')->isEmpty());

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection from Community');
$this->assertEquals('Collection from Community', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1454595297), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1454595297), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertReferences(static::$europeCountries, $collection->field_spatial_coverage);
$this->assertReferences(['Collaboration'], $collection->field_policy_domain);
$this->assertEquals(0, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertStringEndsWith('This group will offer Open Data stakeholders a possibility to find out more about the latest developments around the European Data Portal. ', $collection->field_ar_abstract->value);
$this->assertEquals('content_editor', $collection->field_ar_abstract->format);
$this->assertStringEndsWith("Discover the portal <a href=\"http://www.europeandataportal.eu\">www.europeandataportal.eu</a></p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/epdp_final_logo1-01.png', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/epdp_final_logo1-01.png');
$this->assertTrue($collection->get('field_ar_access_url')->isEmpty());

$collection = $this->loadEntityByLabel('rdf_entity', 'Archived collection');
$this->assertEquals('Archived collection', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1323263112), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1404313500), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'Styles Layer Descriptor',
  'KASPeR - Mapping application of statistical data e-dimensions',
  'Core Location Vocabulary',
  'DCAT application profile for data portals in Europe',
  'Common Reporting Framework XBRL Project',
  'Common Reporting Framework XBRL Project',
  'Evaluation and Report Language (EARL) 1.0 Schema',
], $collection->field_ar_affiliates);
$this->assertTrue($collection->get('field_spatial_coverage')->isEmpty());
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertReferences([
  'La forja de Guadalinex',
  'ACME University',
], $collection->get('field_ar_owner'));
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertTrue($collection->get('field_ar_abstract')->isEmpty());
$this->assertStringEndsWith("suitable for old computers, and netbooks)</li>\r\n</ul>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/guadalinex.JPG', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/guadalinex.JPG');
$this->assertEquals('http://forja.guadalinex.org/', $collection->get('field_ar_access_url')->uri);

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 2 entities having custom section');
$this->assertEquals('Collection with 2 entities having custom section', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1481725653), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1482240807), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertReferences([
  'Digital Signature Service',
], $collection->get('field_ar_affiliates'));
$this->assertReferences(static::$europeCountries, $collection->get('field_spatial_coverage'));
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('EIC (European Interoperability Catalogue)', $collection->get('field_ar_abstract')->value);
$this->assertStringEndsWith("cross-sector setting.</p>\r\n<p>&nbsp;</p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/eic.jpg', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/eic.jpg');
$this->assertTrue($collection->get('field_ar_access_url')->isEmpty());

$collection = $this->loadEntityByLabel('rdf_entity', 'Collection with 1 entity having custom section');
$this->assertEquals('Collection with 1 entity having custom section', $collection->label());
$this->assertEquals('collection', $collection->bundle());
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1282400000), $collection->field_ar_creation_date->value);
$this->assertEquals(gmdate('Y-m-d\TH:i:s', 1458318547), $collection->field_ar_modification_date->value);
$this->assertEquals('default', $collection->graph->value);
$this->assertTrue($collection->get('field_ar_affiliates')->isEmpty());
$this->assertReferences(static::$europeCountries, $collection->get('field_spatial_coverage'));
$this->assertReferences(['Open government'], $collection->field_policy_domain);
$this->assertEquals(1, $collection->field_ar_elibrary_creation->value);
$this->assertTrue($collection->get('field_ar_owner')->isEmpty());
$this->assertTrue($collection->get('field_ar_contact_information')->isEmpty());
$this->assertEquals(1, $collection->field_ar_moderation->value);
$this->assertEquals(0, $collection->field_ar_closed->value);
$this->assertEquals('<p>ISA</p>', $collection->get('field_ar_abstract')->value);
$this->assertStringEndsWith("ISA<sup>2</sup> programme</span></a><span style=\"color:#0064a3;\">.</span></strong></p>\r\n", $collection->field_ar_description->value);
$this->assertEquals('content_editor', $collection->field_ar_description->format);
$logo = File::load($collection->field_ar_logo->target_id);
$this->assertEquals('public://collection/logo/Logo_project-isa-logo-2.jpg', $logo->getFileUri());
$this->assertFileExists('public://collection/logo/Logo_project-isa-logo-2.jpg');
$this->assertEquals('http://ec.europa.eu/isa/', $collection->get('field_ar_access_url')->uri);
