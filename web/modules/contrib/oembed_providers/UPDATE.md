# UPDATING FROM 1.x.x to 2.x.x

In the 1.x.x version of the *oEmbed Providers* module, the 'Allowed Providers'
configuration was global and overrode the 'Remote Video' media source
providers' definition.

In the 2.x.x version, the concept of 'Provider buckets' was added. Instead of
overriding the 'Remote Video' media source, site builders can now define
provider buckets, which are a grouping of providers that are dynamically
exposed as a media source.

When updating from 1.x.x to 2.x.x, the 'Allowed Providers' configuration is
applied to a newly created 'Remote Video' provider bucket. This provides an
upgrade path that maintains the 1.x.x behavior until configured otherwise.

Note: It is not possible to change the media source of an existing media type.
This is a feature of core Media.