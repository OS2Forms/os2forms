# OS2Forms EGBolig

## Module purpose

The aim of this module is to provide integration with EGBolig webform.

## How does it work

It adds custom behaviour to webform ```/os2forms-egbolig-webform```.

Settings page: ```admin/config/system/os2web-datalookup/egbolig```
* **EGBolig webservice URL** - URL of SOAP endpoint.
* **Run EGBolig in TEST mode** - If selected, the EGBolig will run in TEST mode, allowing to use test CPR instead of a real one.
* **Test CPR** - Test CPR will be used instead of the real one. Useful for testing different scenarios.

## Install

OS2Web Data lookup provides integration with Danish data lookup services such as Service platformen or Datafordeler.
```
drush en os2forms_egbolig
```

## Automated testing and code quality
See [OS2Web testing and CI information](https://github.com/OS2Web/docs#testing-and-ci)

## Contribution

Project is opened for new features and os course bugfixes.
If you have any suggestion or you found a bug in project, you are very welcome
to create an issue in github repository issue tracker.
For issue description there is expected that you will provide clear and
sufficient information about your feature request or bug report.

### Code review policy
See [OS2Web code review policy](https://github.com/OS2Web/docs#code-review)

### Git name convention
See [OS2Web git name convention](https://github.com/OS2Web/docs#git-guideline)
