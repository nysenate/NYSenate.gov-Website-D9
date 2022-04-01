const { execSync } = require('child_process');
const { existsSync } = require('fs');

module.exports = {
  candidateThemePaths: [
    '../../../themes/contrib',
    '../../../core/themes',
  ],
  candidateModulePaths: [
    '../../../modules/contrib',
    '../../../core/modules',
  ],
  resolver(base) {
    const [type, name] = base.split('-');
    if (!type || !name) return null;
    let candidatePaths = [];

    // Return expected directory. Thunder Admin Theme is in directory:
    // "[docroot]/themes/contrib/thunder_admin"
    if (type === 'theme') {
      candidatePaths = this.candidateThemePaths;
    }
    else if (type === 'module') {
      candidatePaths = this.candidateModulePaths;
    }
    /* eslint-disable-next-line no-restricted-syntax */
    for (const candidatePath of candidatePaths) {
      const path = `${candidatePath}/${name}`;
      if (existsSync(path)) {
        return path;
      }
    }

    try {
      return execSync(`drush eval "echo DRUPAL_ROOT . '/'. drupal_get_path('${type}', '${name}');"`).toString('utf8');
    }
    catch (err) {
      /* eslint-disable-next-line no-console */
      console.log(`Could not determine directory for ${type} ${name}`);
      return null;
    }
  },
};

