/** 地址（mock 数据，字段与后端 API 一致，使用 snake_case） */
export function genAddress(id) {
  return {
    id: id,
    name: `测试用户${id}`,
    phone: '17612345678',
    province: '甘肃省',
    province_code: '620000',
    city: '甘南藏族自治州',
    city_code: '623000',
    district: '碌曲县',
    district_code: '623026',
    detail: `松日鼎盛大厦${id}层${id}号`,
    full_address: `甘肃省甘南藏族自治州碌曲县松日鼎盛大厦${id}层${id}号`,
    is_default: id === 0,
  };
}

/** 地址列表 */
export function genAddressList(len = 10) {
  return new Array(len).fill(0).map((_, idx) => genAddress(idx));
}
