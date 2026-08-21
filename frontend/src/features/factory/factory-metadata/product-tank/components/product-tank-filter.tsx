"use client";
import { BaseFilter } from "@/components/base-filter";
import { Form, Select } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IFactory } from "../../factory/types";
import { getFactory } from "../../factory/actions";
import { useTranslations } from "next-intl";

interface ProductTankFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const ProductTankFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: ProductTankFilterProps) => {
  const t = useTranslations("Factory.metadata.product_tank");
  const tm = useTranslations("Factory.metadata");

  const mapFactoryOptions = (item: IFactory) => ({
    value: item.factory_id.toString(),
    label: item.factory_name,
  });

  return (
    <BaseFilter
      form={filterForm}
      onFinish={handleFilter}
      onReset={handleResetFilter}>
      <Form.Item name="factory_id" label={t("factory")}>
        <InfiniteScrollSelect<IFactory>
          queryKey={["factories-filter"]}
          fetchFn={(p) => getFactory({ page: p.page, limit: p.limit })}
          mapOption={mapFactoryOptions}
          placeholder={tm("select_factory")}
          style={{ width: 250 }}
          allowClear
        />
      </Form.Item>

      <Form.Item name="product_type" label={t("product_type")}>
        <Select
          options={[
            { value: "SVR 3L", label: "SVR 3L" },
            { value: "SVR 5", label: "SVR 5" },
            { value: "SVR 10", label: "SVR 10" },
            { value: "SVR 20", label: "SVR 20" },
          ]}
          placeholder={t("select_product_type")}
          style={{ width: 200 }}
          allowClear
        />
      </Form.Item>
    </BaseFilter>
  );
};
