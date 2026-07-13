import { useCallback, useEffect, useState } from 'react';
import { archiveService } from '../api/archiveService';
import { apiErrorMessage } from '../utils/helpers';
import { PAGE_SIZE_DEFAULT } from '../utils/constants';

export function useArchives({ category, status, search, perPage = PAGE_SIZE_DEFAULT } = {}) {
  const [items, setItems] = useState([]);
  const [total, setTotal] = useState(0);
  const [page, setPage] = useState(1);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState(null);

  const fetchPage = useCallback(
    async (targetPage = page) => {
      setIsLoading(true);
      setError(null);

      try {
        const result = await archiveService.list({
          page: targetPage,
          perPage,
          category,
          status,
          search,
        });
        setItems(result.items);
        setTotal(result.total);
        setPage(targetPage);
      } catch (err) {
        setError(apiErrorMessage(err, 'Unable to load archives.'));
      } finally {
        setIsLoading(false);
      }
    },
    // eslint-disable-next-line react-hooks/exhaustive-deps
    [category, status, search, perPage]
  );

  useEffect(() => {
    fetchPage(1);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [category, status, search, perPage]);

  const totalPages = Math.max(1, Math.ceil(total / perPage));

  return {
    items,
    total,
    page,
    totalPages,
    perPage,
    isLoading,
    error,
    goToPage: fetchPage,
    refresh: () => fetchPage(page),
  };
}