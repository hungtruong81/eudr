import { BaseFilter } from "@/components/base-filter";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getCompanys } from "@/features/manage/company/actions";
import { Form, Select } from "antd";
import React from "react";
import { useTranslations } from "next-intl";
import { ICompany } from "../../company/types";
import { useUser } from "@/providers/user-context";

interface GroupFilterProps {
  onSearch: (values: any) => void;
  onReset: () => void;
  loading?: boolean;
}

const GroupFilter = ({ onSearch, onReset, loading }: GroupFilterProps) => {
  const [form] = Form.useForm();
  const { isAdmin } = useUser();
  const t = useTranslations("Manage.GroupPermission");
  const tc = useTranslations("Common");

  return (
    <BaseFilter
      form={form}
      onFinish={onSearch}
      onReset={onReset}
      loading={loading}>
      <Form.Item name="company_id" label={tc("company")}>
        {isAdmin && (
          <InfiniteScrollSelect<ICompany>
            queryKey={["companies-select"]}
            fetchFn={getCompanys}
            mapOption={(item) => ({
              label: item.company_name,
              value: String(item.company_id),
            })}
            placeholder={tc("select_company")}
          />
        )}
      </Form.Item>
      <Form.Item name="status" label={tc("status")}>
        <Select
          placeholder={tc("select_status")}
          allowClear
          options={[
            { label: tc("status_all"), value: "all" },
            { label: tc("status_active"), value: "active" },
            { label: tc("status_inactive"), value: "inactive" },
          ]}
        />
      </Form.Item>
    </BaseFilter>
  );
};

export default GroupFilter;
