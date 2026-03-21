// areaData is too large to bundle statically - will be loaded on demand

interface AreaNode {
  label: string;
  value: string;
  children?: AreaNode[];
}

let areaData: AreaNode[] = [];

export const setAreaData = (data: AreaNode[]): void => {
  areaData = data;
};

export const addressParse = (
  provinceName: string,
  cityName: string,
  countryName: string,
): Promise<{
  provinceCode: string;
  cityCode: string;
  districtCode: string;
}> => {
  return new Promise((resolve, reject) => {
    try {
      const province = areaData.find((v) => v.label === provinceName);
      const { value: provinceCode } = province!;
      const city = province!.children!.find((v) => v.label === cityName);
      const { value: cityCode } = city!;
      const country = city!.children!.find((v) => v.label === countryName);
      const { value: districtCode } = country!;
      resolve({ provinceCode, cityCode, districtCode });
    } catch (error) {
      reject('地址解析失败');
    }
  });
};
