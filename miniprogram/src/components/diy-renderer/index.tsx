import { View } from '@tarojs/components';
import { DiyComponent, DiyPagePayload } from './types';
import Banner from '../diy/Banner';
import QuickNav from '../diy/QuickNav';
import ImageAd from '../diy/ImageAd';
import ProductGroup from '../diy/ProductGroup';
import TitleBar from '../diy/TitleBar';
import Gap from '../diy/Gap';
import Divider from '../diy/Divider';
import './index.scss';

interface DiyRendererProps {
  page?: DiyPagePayload | null;
}

const registry: Record<string, (component: DiyComponent) => JSX.Element | null> = {
  banner: (component) => <Banner component={component} />,
  'quick-nav': (component) => <QuickNav component={component} />,
  'image-ad': (component) => <ImageAd component={component} />,
  'product-group': (component) => <ProductGroup component={component} />,
  'title-bar': (component) => <TitleBar component={component} />,
  gap: (component) => <Gap component={component} />,
  divider: (component) => <Divider component={component} />,
};

export function renderDiyComponent(component: DiyComponent): JSX.Element | null {
  if (component.enabled === false) return null;
  const renderer = registry[component.type];
  return renderer ? renderer(component) : null;
}

export default function DiyRenderer({ page }: DiyRendererProps) {
  const components = page?.components || [];
  if (!page?.page || components.length === 0) return null;

  return (
    <View className="diy-renderer">
      {components.map((component) => (
        <View key={component.id} className="diy-renderer__item">
          {renderDiyComponent(component)}
        </View>
      ))}
    </View>
  );
}
