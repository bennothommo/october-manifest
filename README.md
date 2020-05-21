# October CMS Manifest

A proof-of-concept of a manifest for October CMS updates. The manifest will contain a history of October CMS builds and the changes between builds. With this, the user will be able to compare their October CMS installation with the manifest to determine the build in use on their install, and whether the installation has been modified.

## Usage

### Generate a manifest

To generate a manifest file, simply download a copy of this repository and run the following within that folder:

```bash
php generate.php manifest.json
```

This will download all builds of October CMS from build 420 into a `tmp` subfolder and will extract them into separate folders. Once all available builds are downloaded, the script will generate a `manifest.json` file which will contain the manifest from build 420 up until the latest build.

The first argument can be changed to place the manifest file anywhere you like.

> **Note:** Although the script has been set up only to extract the necessary files (ie. the *modules/backend* and *modules/system* folders), you'll still need at least 1GB of space available to store the builds. Once the manifest is generated though, you won't need the build folders anymore. It is handy to keep them though if you intend to continually generate the manifest, as it will save you having to re-download the builds.

### Compare October CMS with manifest

Once a manifest has been generated, you can compare an October CMS installation against the manifest to determine the build of that installation.

```bash
php compare.php [path to October]
```

If the Backend and System modules have been left unmodified, it will detect that you have an unmodified version of October CMS:

```
You are running an unmodified version of build 446 of October CMS.
```

If, however, there have been modifications - such as if you are working with the development build - it will try to best guess the installed version and will say something like this, depending on how extensive the modifications are:

```
You are running a modified version of build 465 of October CMS. (Probability: 88.91%)
```
