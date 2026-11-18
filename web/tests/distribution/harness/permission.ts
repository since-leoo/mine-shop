export default (role: string) => role === 'SuperAdmin' && (window as any).__superAdmin !== false
