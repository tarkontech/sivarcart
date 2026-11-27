import '../../css/storefront.css'

import { createStorefrontApp } from './app'

/**
 * The entry of the bundle of the store.
 *
 * The bundle travels with the package and the package serves it, so a
 * merchant that installs the package has the store without Node and without
 * a build step.
 */
createStorefrontApp()
