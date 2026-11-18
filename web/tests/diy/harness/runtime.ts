export const useTrans = () => ({ globalTrans: (key: string) => key })

// Supply permissions to the real hasAuth implementation without loading the login/session store.
export const useUserStore = () => ({ getPermissions: () => JSON.parse(localStorage.getItem('diy-test-permissions') || '["*"]') })
