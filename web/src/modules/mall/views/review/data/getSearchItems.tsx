import type { MaSearchItem } from '@mineadmin/search'
import { useI18n } from 'vue-i18n'

export default function getSearchItems(): MaSearchItem[] {
  const { t } = useI18n()
  return [
    {
      label: () => t('mall.review.statusLabel'),
      prop: 'status',
      render: () => (
        <el-select clearable placeholder={t('mall.review.allStatus')}>
          <el-option label={t('mall.review.pending')} value="pending" />
          <el-option label={t('mall.review.approved')} value="approved" />
          <el-option label={t('mall.review.rejected')} value="rejected" />
        </el-select>
      ),
    },
    {
      label: () => t('mall.review.ratingLabel'),
      prop: 'rating',
      render: () => (
        <el-select clearable placeholder={t('mall.review.allRating')}>
          <el-option label={t('mall.review.star5')} value={5} />
          <el-option label={t('mall.review.star4')} value={4} />
          <el-option label={t('mall.review.star3')} value={3} />
          <el-option label={t('mall.review.star2')} value={2} />
          <el-option label={t('mall.review.star1')} value={1} />
        </el-select>
      ),
    },
    {
      label: () => t('mall.product.productName'),
      prop: 'product_name',
      render: 'input',
      renderProps: { placeholder: t('mall.review.searchProduct'), clearable: true },
    },
    {
      label: () => t('mall.review.createdAtLabel'),
      prop: 'created_at',
      render: () => (
        <el-date-picker
          type="daterange"
          value-format="YYYY-MM-DD"
          start-placeholder={t('mall.review.startDate')}
          end-placeholder={t('mall.review.endDate')}
          style="width: 100%"
        />
      ),
    },
  ]
}
