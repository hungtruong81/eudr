"use client";
import { BaseFilter } from "@/components/base-filter";
import { Form, Select } from "antd";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { IFactory } from "../../factory/types";
import { getFactory } from "../../factory/actions";
import { useTranslations } from "next-intl";

interface RawMaterialTankFilterProps {
  filterForm: any;
  handleFilter: (values: any) => void;
  handleResetFilter: () => void;
}

export const RawMaterialTankFilter = ({
  filterForm,
  handleFilter,
  handleResetFilter,
}: RawMaterialTankFilterProps) => {
  const t = useTranslations("Factory.metadata.raw_material_tank");
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
          fetchFn={getFactory}
          mapOption={mapFactoryOptions}
          placeholder={tm("select_factory")}
          allowClear
        />
      </Form.Item>

      <Form.Item name="tank_type" label={t("tank_type")}>
        <Select
          options={[
            { value: "latex", label: t("latex") },
            { value: "scrap_rubber", label: t("scrap_rubber") },
            { value: "mixed", label: t("mixed") },
          ]}
          placeholder={t("select_tank_type")}
          allowClear
        />
      </Form.Item>
    </BaseFilter>
  );
};
