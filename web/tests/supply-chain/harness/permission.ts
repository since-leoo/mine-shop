export default (code: string) => !(window as unknown as { __deniedPermissions?: string[] }).__deniedPermissions?.includes(code)
