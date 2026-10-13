/** API 分类可能直接挂商品，也可能有二/三级节点；叶级一级分类仍可进入商品列表。 */
export function categoryLeaves(category: any): any[] {
  const children = Array.isArray(category?.children) ? category.children : [];
  if (!children.length) return category ? [category] : [];
  return children.flatMap(categoryLeaves);
}

export function normalizeCategories(nodes: any[] = []): any[] {
  return nodes.map((node) => ({
    ...node,
    groupId: String(node.groupId || node.categoryId || node.id || ''),
    name: node.name || node.title || '',
    thumbnail: node.thumbnail || node.image || node.icon || '',
    children: Array.isArray(node.children) ? normalizeCategories(node.children) : [],
  }));
}
