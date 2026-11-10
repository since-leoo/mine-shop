import type { StorefrontPlugin } from '../../common/plugin-hooks';
import { captureDistributionInvite, clearDistributionInvite, createDistributionShare, getDistributionInviteForRegistration, notifyDistributionBinding } from './share';
import { DistributionProfileEntry, DistributionMenuEntry } from './components/UserCenterEntry';
import DistributionCenter from './components/DistributionCenter';

const distribution: StorefrontPlugin = {
  name: 'distribution',
  onAppEnter: captureDistributionInvite,
  async beforeAuth(kind, payload) {
    return kind === 'h5_login' ? payload : { ...payload, ...await getDistributionInviteForRegistration() };
  },
  afterAuth(result) {
    clearDistributionInvite();
    notifyDistributionBinding(result);
  },
  async shareProduct(options, current) {
    try {
      return await createDistributionShare({ ...options, target: 'product' });
    } catch (error: any) {
      // Frontend bundles can outlive a removed backend plugin; ordinary sharing still works.
      if (Number(error?.statusCode || error?.code) === 404) return current;
      throw error;
    }
  },
  slots: { 'user-profile-right': DistributionProfileEntry, 'user-center-menu': DistributionMenuEntry },
  renderers: { 'distribution-center': DistributionCenter },
};
export default distribution;
