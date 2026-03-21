interface AddressPromiseEntry {
  resolver: (value: any) => void;
  rejecter: (reason?: any) => void;
}

let addressPromise: AddressPromiseEntry[] = [];

/** 地址编辑Promise */
export const getAddressPromise = () => {
  let resolver!: (value: any) => void;
  let rejecter!: (reason?: any) => void;
  const nextPromise = new Promise((resolve, reject) => {
    resolver = resolve;
    rejecter = reject;
  });

  addressPromise.push({ resolver, rejecter });

  return nextPromise;
};

/** 用户保存了一个地址 */
export const resolveAddress = (address: any) => {
  const allAddress = [...addressPromise];
  addressPromise = [];

  console.info('用户保存了一个地址', address);

  allAddress.forEach(({ resolver }) => resolver(address));
};

/** 取消编辑 */
export const rejectAddress = () => {
  const allAddress = [...addressPromise];
  addressPromise = [];

  allAddress.forEach(({ rejecter }) => rejecter(new Error('cancel')));
};
