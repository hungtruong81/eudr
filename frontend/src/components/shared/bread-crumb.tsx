import Link from "next/link";

interface BreadcrumbProps {
  items: { label: string; href: string; isLast: boolean }[];
}

const Breadcrumb: React.FC<BreadcrumbProps> = ({ items }) => {
  const truncateLabel = (label: string, maxLength: number = 50) => {
    return label.length > maxLength ? `${label.slice(0, maxLength)}...` : label;
  };

  return (
    <nav aria-label="breadcrumb" className="flex items-center space-x-2">
      {items.map((item, index) => (
        <div key={index} className="flex items-center">
          {/* {!item.isLast && index !== 0 ? (
            <Link
              href={item.href}
              className="text-primary hover:underline hidden md:block"
            >
              {truncateLabel(item.label)}
            </Link>
          ) : (
            <span
              className={
                index === 0
                  ? "text-gray-400 cursor-not-allowed hidden md:block"
                  : ""
              }
            >
              {truncateLabel(item.label)}
            </span>
          )} */}
          <span
            className={
              index === 0
                ? "text-gray-400 cursor-not-allowed hidden md:block"
                : ""
            }>
            {truncateLabel(item.label)}
          </span>
          {index < items.length - 1 && <span className="mx-1">/</span>}
        </div>
      ))}
    </nav>
  );
};

export default Breadcrumb;
