import { Text, View } from '@tarojs/components';
import { DiyComponent } from '../../diy-renderer/types';
import { navigateDiyLink } from '../../diy-renderer/link';
import './index.scss';

interface Props {
  component: DiyComponent<Record<string, never>, { placeholder?: string; shape?: string; target?: string }>;
}

export default function SearchBar({ component }: Props) {
  return (
    <View className={`diy-search-bar ${component.props?.shape === 'square' ? 'diy-search-bar--square' : ''}`} onClick={() => navigateDiyLink({ type: 'page', path: component.props?.target || '/pages/search/index' })}>
      <Text className="diy-search-bar__icon">⌕</Text>
      <Text className="diy-search-bar__text">{component.props?.placeholder || '搜索商品'}</Text>
    </View>
  );
}
