#API mockup application
Change your api url as shown here:
```php
$config['api.settings']['api_base_url'] = 'http://mockup_front/|'.base64_encode('http://api.example.com').'|';
```
#ROADMAP:
 1. settings file to override some parts
 2. soap support (currently not needed)
 3. bucket support to separate different mockups
 4. UI for reviews and mock updates
