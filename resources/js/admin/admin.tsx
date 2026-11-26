import '../../css/admin.css'

import { createPanelApp } from './app'

/**
 * The entry of the bundle of the administration panel.
 *
 * The bundle travels with the package and the package serves it, so a
 * merchant that installs the package has the panel without Node and without a
 * build step.
 */
createPanelApp()
