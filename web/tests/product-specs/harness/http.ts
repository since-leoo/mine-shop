import useTestHttp from '../../supply-chain/harness/http'

export default () => ({
  ...useTestHttp(),
  delete: async (url: string) => (await fetch(url, { method: 'DELETE' })).json(),
})
