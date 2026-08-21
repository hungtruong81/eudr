"use client";

import { REGISTER_TYPE, REGISTER_TYPE_LABEL } from "@/constants/register-types";
import { useUser } from "@/providers/user-context";
import { Col, Input, Row, Select, Form } from "antd";
import React from "react";
import { useTranslations } from "next-intl";
import { InfiniteScrollSelect } from "@/components/infinite-scroll";
import { getCompanys } from "../../company/actions";
import { ICompany } from "../../company/types";
import { BaseFilter } from "@/components/base-filter";

interface UserFilterProps {
  onSearch: (values: any) => void;
  onClear: () => void;
}

const UserFilter = ({ onSearch, onClear }: UserFilterProps) => {
  const { isAdmin } = useUser();
  const t = useTranslations("Manage.User");
  const tc = useTranslations("Common");
  const tr = useTranslations("RegisterType");
  const handleFinish = (values: any) => {
    onSearch({
      ...values,
    });
  };
  const allowedRegisterTypes = [
    REGISTER_TYPE.FARMER,
    REGISTER_TYPE.PURCHASER,
    REGISTER_TYPE.TRANSPORTER,
    REGISTER_TYPE.FACTORY,
    REGISTER_TYPE.BUSINESS,
  ];

  return (
    <BaseFilter onFinish={handleFinish} onReset={onClear}>
      <Form.Item name="search" className="mb-0" label={tc("search")}>
        <Input placeholder={tc("search_placeholder")} allowClear />
      </Form.Item>
      <Form.Item name="register_type" className="mb-0" label={t("register_type")}>
        <Select
          placeholder={t("register_type_placeholder")}
          allowClear
          style={{ width: "100%", minWidth: 200 }}
          options={allowedRegisterTypes.map((type) => ({
            label: tr(type),
            value: type,
          }))}
        />
      </Form.Item>
      {isAdmin && (
        <Form.Item name="company_id" className="mb-0" label={tc("company")}>
          <InfiniteScrollSelect<ICompany>
            placeholder={tc("select_company")}
            queryKey={["company-list-filter"]}
            fetchFn={getCompanys}
            mapOption={(item) => ({
              label: item.company_name,
              value: String(item.company_id),
            })}
          />
        </Form.Item>
      )}
    </BaseFilter>
  );
};

export default UserFilter;
