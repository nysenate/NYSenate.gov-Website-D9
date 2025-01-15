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
 * Revised: 2019-01-11 - updated 17 entries for the 2019-2020 session
 * Revised: 2019-12-11 - added pattern for Senator Borrello
 * Revised: 2021-01-04 - updated 14 entries for the 2021-2022 session
 * Revised: 2022-02-22 - added pattern for Senator Cleare
 * Revised: 2023-01-06 - updated 14 entries for the 2023-2024 session
 * Revised: 2023-06-23 - updated microsite URL for Senator Harckham
 * Revised: 2024-01-02 - updated 5 entries for the 2025-2026 session
 *
 */

// This function returns an array of senator, committee, and virtual host
// regexps that map to senator microsites, committee pages, and virtual host
// microsites.
function get_nyss_url_patterns()
{
  return [
    'senators'   => get_nyss_senator_patterns(),
    'committees' => get_nyss_committee_patterns(),
    'vhosts'     => get_nyss_vhost_patterns()
  ];
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
    'jacob-ashby'             => '(jacob|jake)?ashby',
    'jamaal-bailey'           => '(jamaal)?bailey',
    'april-baskin'            => '(april)?(mccants\-?)?baskin',
    'george-borrello'         => '(george)?borrello',
    'jabari-brisport'         => '(jabari)?brisport',
    'samra-g-brouk'           => '(samra)?brouk',
    'siela-bynoe'             => '(siela)?bynoe',
    'patricia-canzoneri-fitzpatrick' => '(patricia|pat)?canzoneri',
    'stephen-t-chan'          => '(stephen)?chan',
    'cordell-cleare'          => '(cordell)?cleare',
    'leroy-comrie'            => '(leroy)?comrie',
    'jeremy-cooney'           => '(jeremy)?cooney',
    'patricia-fahy'           => '(pat(ricia)?)?fahy',
    'simcha-felder'           => '(simcha)?felder',
    'nathalia-fernandez'      => '(nathalia)?fernandez',
    'patrick-m-gallivan'      => '(pat(rick)?)?gallivan',
    'michael-gianaris'        => '(michael)?gianaris',
    'kristen-gonzalez'        => '(kristen)?gonzalez',
    'andrew-gounardes'        => '(andrew)?gounardes',
    'joseph-griffo'           => '(joseph|joe)?griffo',
    'pete-harckham'           => '(peter)?harckham',
    'pamela-helming'          => '(pamela|pam)?helming',
    'michelle-hinchey'        => '(michelle)?hinchey',
    'brad-hoylman'            => '(brad)?hoylman',
    'robert-jackson'          => '(robert)?jackson',
    'brian-kavanagh'          => '(brian)?kavanagh',
    'liz-krueger'             => '(liz)?krueger',
    'andrew-j-lanza'          => '(andrew)?lanza',
    'john-liu'                => '(john)?liu',
    'monica-r-martinez'       => '(monica)?martinez',
    'jack-m-martins'          => '(jack)?martins',
    'mario-r-mattera'         => '(mario)?mattera',
    'rachel-may'              => '(rachel)?may',
    'shelley-mayer'           => '(shelley)?mayer',
    'dean-murray'             => '(dean)?murray',
    'zellnor-myrie'           => '(zellnor)?myrie',
    'peter-oberacker'         => '(peter)?oberacker',
    'thomas-f-omara'          => '(thomas)?omara',
    'robert-g-ortt'           => '(robert)?ortt',
    'anthony-h-palumbo'       => '(anthony)?palumbo',
    'kevin-s-parker'          => '(kevin)?parker',
    'roxanne-j-persaud'       => '(roxanne)?persaud',
    'jessica-ramos'           => '(jessica)?ramos',
    'steven-d-rhoads'         => '(steven)?rhoads',
    'gustavo-rivera'          => '(gustavo)?rivera',
    'robert-rolison'          => '(robert)?rolison',
    'christopher-j-ryan'      => '(chris(topher)?ryan|ryanc)',
    'sean-m-ryan'             => '(sean)?ryan',
    'julia-salazar'           => '(julia)?salazar',
    'james-sanders-jr'        => '(james)?sanders',
    'luis-r-sepulveda'        => '(luis)?sepulveda',
    'jose-m-serrano'          => '(jose)?serrano',
    'james-skoufis'           => '(james)?skoufis',
    'jessica-scarcella-spanton' => '(jessica)?spanton',
    'toby-ann-stavisky'       => '(toby(ann)?)?stavisky',
    'daniel-g-stec'           => '(dan(iel)?)?stec',
    'andrea-stewart-cousins'  => '(andrea)?stewart\-?cousins',
    'james-tedisco'           => '(james|jim)?tedisco',
    'kevin-thomas'            => '(kevin)?thomas',
    'mark-walczyk'            => '(mark)?walczyk',
    'lea-webb'                => '(lea)?webb',
    'bill-weber'              => '(william|bill)?weber',
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
