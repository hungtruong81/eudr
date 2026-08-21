"use client";

import React, { Suspense, useEffect } from "react";
import { Input, Button, Card, Spin, Space, Typography, Empty } from "antd";
import { useQuery } from "@tanstack/react-query";
import { getTicketByContractCode } from "./actions";
import TransactionTicketInvoice from "./components/transaction-ticket-invoice";
import { useSearchParams, useRouter, usePathname } from "next/navigation";

const { Title, Text } = Typography;

import { useTranslations } from "next-intl";

const SearchContent = () => {
  const t = useTranslations("TransactionTicket");
  const tc = useTranslations("Common");
  const searchParams = useSearchParams();
  const router = useRouter();
  const pathname = usePathname();
  const cParam = searchParams.get("c");

  const [contractCode, setContractCode] = React.useState(cParam || "");
  const [searchCode, setSearchCode] = React.useState(cParam || "");

  useEffect(() => {
    if (cParam && cParam !== searchCode) {
      setSearchCode(cParam);
      setContractCode(cParam);
    }
  }, [cParam, searchCode]);

  const {
    data: ticketDetail,
    isLoading,
    isError,
  } = useQuery({
    queryKey: ["transaction-ticket-search", searchCode],
    queryFn: () => getTicketByContractCode(searchCode),
    enabled: !!searchCode,
    retry: false,
  });

  const handleSearch = () => {
    const trimmedCode = contractCode.trim();
    if (trimmedCode) {
      setSearchCode(trimmedCode);
      // Update URL with search code
      const params = new URLSearchParams(searchParams.toString());
      params.set("c", trimmedCode);
      router.push(`${pathname}?${params.toString()}`);
    }
  };

  const ticket = ticketDetail?.data;

  return (
    <>
      <Card style={{ marginBottom: "24px" }}>
        <Space orientation="vertical" style={{ width: "100%" }} size="large">
          <div>
            <Title level={3} style={{ margin: 0 }}>
              {t("lookup_title")}
            </Title>
            <Text type="secondary">
              {t("lookup_description")}
            </Text>
          </div>

          <Space.Compact style={{ width: "100%" }}>
            <Input
              placeholder={t("enter_contract_placeholder")}
              value={contractCode}
              onChange={(e) => setContractCode(e.target.value)}
              onPressEnter={handleSearch}
              size="large"
            />
            <Button
              type="primary"
              size="large"
              onClick={handleSearch}
              loading={isLoading}>
              {t("lookup_button")}
            </Button>
          </Space.Compact>
        </Space>
      </Card>

      <Spin spinning={isLoading}>
        {ticket ? (
          <TransactionTicketInvoice ticket={ticket} />
        ) : searchCode && !isLoading ? (
          <Card>
            <Empty
              description={
                isError
                  ? t("no_invoice_found")
                  : t("no_data_for_contract")
              }
            />
          </Card>
        ) : (
          !searchCode && (
            <Card style={{ textAlign: "center", padding: "40px 0" }}>
              <Empty description={t("lookup_start_placeholder")} />
            </Card>
          )
        )}
      </Spin>
    </>
  );
};

const Search = () => {
  const tc = useTranslations("Common");
  return (
    <Suspense
      fallback={
        <Card style={{ textAlign: "center", padding: "40px 0" }}>
          <Spin description={tc("loading")} />
        </Card>
      }>
      <SearchContent />
    </Suspense>
  );
};

export default Search;
