import { Card, Col, Row, Statistic } from "antd";
import { useTranslations } from "next-intl";
import React, { useMemo } from "react";
import { IProductLotInventory } from "@/features/factory/lot/types";
import { Package, Weight, Layers, CheckCircle2 } from "lucide-react";

interface InventoryStatsProps {
  data: IProductLotInventory[];
  totalRecords?: number;
  loading?: boolean;
}

const InventoryStats = ({
  data,
  totalRecords,
  loading,
}: InventoryStatsProps) => {
  const t = useTranslations("Inventory");

  const stats = useMemo(() => {
    return {
      totalItems: totalRecords || data.length,
      totalWeight: data.reduce(
        (acc, item) => acc + Number(item.total_weight || 0),
        0,
      ),
      totalBlocks: data.reduce(
        (acc, item) => acc + Number(item.total_blocks || 0),
        0,
      ),
      availableWeight: data
        .filter((item) => item.status === "available")
        .reduce((acc, item) => acc + Number(item.total_weight || 0), 0),
    };
  }, [data, totalRecords]);

  return (
    <Row gutter={[16, 16]} className="mb-6">
      <Col xs={24} sm={12} lg={6}>
        <Card
          loading={loading}
          className="shadow-sm border-t-4 border-t-blue-500 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
          <Statistic
            title={
              <span className="text-muted-foreground font-medium">
                {t("stats_total_lots")}
              </span>
            }
            value={stats.totalItems}
            prefix={<Package className="size-5 text-blue-500 mr-2" />}
          />
        </Card>
      </Col>
      <Col xs={24} sm={12} lg={6}>
        <Card
          loading={loading}
          className="shadow-sm  rounded-xl overflow-hidden hover:shadow-md transition-shadow">
          <Statistic
            title={
              <span className="text-muted-foreground font-medium">
                {t("stats_total_weight")}
              </span>
            }
            value={stats.totalWeight}
            precision={2}
            suffix={<span className="text-xs ml-1">kg</span>}
            prefix={<Weight className="size-5 text-green-500 mr-2" />}
          />
        </Card>
      </Col>
      <Col xs={24} sm={12} lg={6}>
        <Card
          loading={loading}
          className="shadow-sm border-t-4 border-t-orange-500 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
          <Statistic
            title={
              <span className="text-muted-foreground font-medium">
                {t("stats_total_blocks")}
              </span>
            }
            value={stats.totalBlocks}
            prefix={<Layers className="size-5 text-orange-500 mr-2" />}
          />
        </Card>
      </Col>
      <Col xs={24} sm={12} lg={6}>
        <Card
          loading={loading}
          className="shadow-sm border-t-4 border-t-emerald-500 rounded-xl overflow-hidden hover:shadow-md transition-shadow">
          <Statistic
            title={
              <span className="text-muted-foreground font-medium">
                {t("stats_available_weight")}
              </span>
            }
            value={stats.availableWeight}
            precision={2}
            suffix={<span className="text-xs ml-1">kg</span>}
            prefix={<CheckCircle2 className="size-5 text-emerald-500 mr-2" />}
          />
        </Card>
      </Col>
    </Row>
  );
};

export default InventoryStats;
