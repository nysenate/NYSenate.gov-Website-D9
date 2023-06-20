<?php

/**
 * nyss_url_patterns.inc.php - Map senator microsites and committee pages
 * to their corresponding URL patterns.
 *
 * Organization: New York State Senate
 * Project: NYSenate.gov Public Website
 * Author: Ken Zalewski
 * Date: 2017-12-30
 * Revised: 2018-02-09 - converted to functions; added committees
 * Revised: 2018-05-29 - added virtual host patterns
 *
 */

// This function returns an array of senator, committee, and virtual host
// regexps that map to senator microsites, committee pages, and virtual host
// microsites.
function get_nyss_url_patterns()
{
  return [ 'senators'   => get_nyss_senator_patterns(),
           'committees' => get_nyss_committee_patterns(),
           'vhosts'     => get_nyss_vhost_patterns() ];
} // get_nyss_url_patterns()


// This function returns an array that maps senator "vanity" subdomains to
// their corresponding senator microsites.
// The microsite basename is the key, and the subdomain pattern is the value.
// The "john-doe" microsite is used for testing the rule; it does not exist.
function get_nyss_senator_patterns()
{
  return array(
    'john-doe'                => '(john)?doe',
    'joseph-p-addabbo-jr'     => '(joseph|joe)?addabbo',
    'frederick-j-akshar-ii'   => '(frederick|fred)?akshar',
    'jamaal-bailey'           => '(jamaal)?bailey',
    'alessandra-biaggi'       => '(alessandra)?biaggi',
    'george-borrello'         => '(george)?borrello',
    'phil-boyle'              => '(phil(ip)?|p)?boyle',
    'neil-d-breslin'          => '(neil)?breslin',
    'jabari-brisport'         => '(jabari)?brisport',
    'john-e-brooks'           => '(john)?brooks',
    'samra-g-brouk'           => '(samra)?brouk',
    'cordell-cleare'          => '(cordell)?cleare',
    'leroy-comrie'            => '(leroy)?comrie',
    'jeremy-cooney'           => '(jeremy)?cooney',
    'simcha-felder'           => '(simcha)?felder',
    'patrick-m-gallivan'      => '(pat(rick)?)?gallivan',
    'james-gaughran'          => '(james)?gaughran',
    'michael-gianaris'        => '(michael)?gianaris',
    'andrew-gounardes'        => '(andrew)?gounardes',
    'joseph-griffo'           => '(joseph|joe)?griffo',
    'peter-harckham'          => '(peter)?harckham',
    'pamela-helming'          => '(pamela|pam)?helming',
    'michelle-hinchey'        => '(michelle)?hinchey',
    'brad-hoylman'            => '(brad)?hoylman',
    'robert-jackson'          => '(robert)?jackson',
    'daphne-jordan'           => '(daphne)?jordan',
    'todd-kaminsky'           => '(todd)?kaminsky',
    'anna-kaplan'             => '(anna)?kaplan',
    'brian-kavanagh'          => '(brian)?kavanagh',
    'timothy-m-kennedy'       => '(tim(othy)?)?kennedy',
    'liz-krueger'             => '(liz)?krueger',
    'andrew-j-lanza'          => '(andrew)?lanza',
    'john-liu'                => '(john)?liu',
    'john-w-mannion'          => '(john)?mannion',
    'mike-martucci'           => '(michael|mike)?martucci',
    'mario-r-mattera'         => '(mario)?mattera',
    'rachel-may'              => '(rachel)?may',
    'shelley-mayer'           => '(shelley)?mayer',
    'zellnor-myrie'           => '(zellnor)?myrie',
    'peter-oberacker'         => '(peter)?oberacker',
    'thomas-f-omara'          => '(thomas)?omara',
    'robert-g-ortt'           => '(robert)?ortt',
    'anthony-h-palumbo'       => '(anthony)?palumbo',
    'kevin-s-parker'          => '(kevin)?parker',
    'roxanne-j-persaud'       => '(roxanne)?persaud',
    'jessica-ramos'           => '(jessica)?ramos',
    'edward-rath-iii'         => '(ed(ward)?)?rath',
    'elijah-reichlin-melnick' => '(elijah)?reichlin(\-?melnick)?',
    'patty-ritchie'           => '(patricia|patty)?ritchie',
    'gustavo-rivera'          => '(gustavo)?rivera',
    'sean-m-ryan'             => '(sean)?ryan',
    'julia-salazar'           => '(julia)?salazar',
    'james-sanders-jr'        => '(james)?sanders',
    'diane-j-savino'          => '(diane)?savino',
    'luis-r-sepulveda'        => '(luis)?sepulveda',
    'sue-serino'              => '(susan|sue)?serino',
    'jose-m-serrano'          => '(jose)?serrano',
    'james-skoufis'           => '(james)?skoufis',
    'toby-ann-stavisky'       => '(toby(ann)?)?stavisky',
    'daniel-g-stec'           => '(dan(iel)?)?stec',
    'andrea-stewart-cousins'  => '(andrea)?stewart\-?cousins',
    'james-tedisco'           => '(james|jim)?tedisco',
    'kevin-thomas'            => '(kevin)?thomas',
    'alexis-weik'             => '(alexis)?weik',
  );
} // get_nyss_senator_patterns()


// This function returns an array that maps committee subdomains to
// their corresponding committee pages.
// The committee page basename is the key & the subdomain pattern is the value.
function get_nyss_committee_patterns()
{
  return array(
    'administrative-regulations-review-commission-arrc' => 'regulations',
    'aging'                                    => 'aging',
    'agriculture'                              => 'agriculture',
    'alcoholism-and-drug-abuse'                => 'alcoholism',
    'banks'                                    => 'banks',
    'children-and-families'                    => '(children|families)',
    'cities'                                   => 'cities',
    'civil-service-and-pensions'               => '(civilservice|pensions)',
    'codes'                                    => 'codes',
    'consumer-protection'                      => 'consumerprotection',
    'corporations-authorities-and-commissions' => '(corporations|authorities)',
    'crime-victims-crime-and-correction'       => '(crime|corrections)',
    'cultural-affairs-tourism-parks-and-recreation' => '(parks|tourism)',
    'education'                                => 'education',
    'elections'                                => 'elections',
    'energy-and-telecommunications'            => '(energy|telecom)',
    'environmental-conservation'               => '(environment|conservation)',
    'ethics-and-internal-governance'           => '(ethics|governance)',
    'finance'                                  => 'finance',
    'health'                                   => 'health',
    'heroin-task-force'                        => 'heroin',
    'higher-education'                         => 'highered(ucation)?',
    'housing-construction-and-community-development' => 'housing',
    'infrastructure-and-capital-investment'    => 'infrastructure',
    'insurance'                                => 'insurance',
    'investigations-and-government-operations' => 'investigations',
    'judiciary'                                => 'judiciary',
    'labor'                                    => 'labor',
    'legislative-commission-rural-resources'   => 'ruralresources',
    'libraries'                                => 'libraries',
    'local-government'                         => 'localgov(ernment)?',
    'mental-health-and-developmental-disabilities' => 'mentalhealth',
    'new-york-city-education-subcommittee'     => 'nyceducation',
    'new-york-state-conference-black-senators' => 'blacksenators',
    'puerto-ricanlatino-caucus'                => '(puertoricans|latinos)',
    'racing-gaming-and-wagering'               => '(racing|gaming|wagering)',
    'rules'                                    => 'rules',
    'social-services'                          => 'socialservices',
    'state-native-american-relations'          => 'nativeamericans',
    'technology-and-innovation'                => '(technology|innovation)',
    'transportation'                           => 'transportation',
    'veterans-homeland-security-and-military-affairs' => '(veterans|military)',
  );
} // get_nyss_committee_patterns()


// This function returns an array that maps virtual hostnames to context paths.
// The context path is the key and the virtual host pattern is the value.
function get_nyss_vhost_patterns()
{
  return array(
    '/nyread'    => '^((www\.)?nyread.com|summerreading.nysenate.gov)$',
  );
} // get_nyss_vhost_patterns()

