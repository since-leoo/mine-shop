export default (code: string) => !(window as any).__deniedPermissions?.includes(code)
