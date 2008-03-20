{def $url=concat('http://', ezini( 'SiteSettings', 'SiteURL', 'site.ini' ))}
{def $subject = concat( 'Payment warning from ', $url )}
Dear Customer,

Please check your payment options.

- Your credit card might be out of funds.
- Your credit card might be expired.
- Your credit card might expire soon.

Yours,
{ezini( 'SiteSettings', 'SiteName', 'site.ini' )}
{$url}
