<?php
/**
 * Solutions (Riešenia) Admin View
 * Search and manage solutions by product and problem
 */

use HelpDesk\Models\GeneralGuide;
use HelpDesk\Models\Product;
use HelpDesk\Models\Bug;
use HelpDesk\Models\GuideCategory;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$guides = GeneralGuide::get_all_active();
$products = Product::get_all();
$categories = GuideCategory::get_all();

// Get all problems/bug codes
$bugs = Bug::get_all();
?>

<div class="wrap">
    <h1><?php echo esc_html__( 'Riešenia (Aplikačná Podpora)', HELPDESK_TEXT_DOMAIN ); ?></h1>

    <div class="helpdesk-admin-container">
        <!-- Search and Filter Section -->
        <div style="margin-bottom: 20px; padding: 20px; background: #fff; border: 1px solid #ccc; border-radius: 4px;">
            <h3><?php echo esc_html__( 'Vyhľadávanie riešení', HELPDESK_TEXT_DOMAIN ); ?></h3>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <!-- Search by text -->
                <div>
                    <label for="solution-search-text" style="display: block; font-weight: 500; margin-bottom: 8px;">
                        <?php echo esc_html__( 'Vyhľadávanie textu', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <input type="text" id="solution-search-text" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;" placeholder="<?php echo esc_attr__( 'Hľadať v názve alebo popise...', HELPDESK_TEXT_DOMAIN ); ?>">
                </div>

                <!-- Search by product -->
                <div>
                    <label for="solution-filter-product" style="display: block; font-weight: 500; margin-bottom: 8px;">
                        <?php echo esc_html__( 'Filtrovať podľa produktu', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="solution-filter-product" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php echo esc_html__( '-- Všetky produkty --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $products as $product ) : ?>
                            <option value="<?php echo esc_attr( $product['id'] ); ?>"><?php echo esc_html( $product['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Search by problem -->
                <div>
                    <label for="solution-filter-problem" style="display: block; font-weight: 500; margin-bottom: 8px;">
                        <?php echo esc_html__( 'Filtrovať podľa problému', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="solution-filter-problem" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php echo esc_html__( '-- Všetky problémy --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php if ( ! empty( $bugs ) ) : ?>
                            <?php foreach ( $bugs as $bug ) : ?>
                                <option value="<?php echo esc_attr( $bug->id ); ?>"><?php echo esc_html( $bug->nazov ); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <!-- Filter by category -->
                <div>
                    <label for="solution-filter-category" style="display: block; font-weight: 500; margin-bottom: 8px;">
                        <?php echo esc_html__( 'Filtrovať podľa typu', HELPDESK_TEXT_DOMAIN ); ?>
                    </label>
                    <select id="solution-filter-category" class="widefat" style="padding: 8px; border: 1px solid #ddd; border-radius: 4px;">
                        <option value=""><?php echo esc_html__( '-- Všetky typy --', HELPDESK_TEXT_DOMAIN ); ?></option>
                        <?php foreach ( $categories as $category ) : ?>
                            <option value="<?php echo esc_attr( $category['nazov'] ); ?>"><?php echo esc_html( $category['nazov'] ); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Reset filters button -->
                <div style="display: flex; align-items: flex-end; gap: 10px;">
                    <button id="btn-search-solutions" class="button button-primary" style="flex: 1; padding: 8px;">
                        <?php echo esc_html__( '🔍 Vyhľadať', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                    <button id="btn-reset-solutions-filters" class="button" style="flex: 1; padding: 8px;">
                        <?php echo esc_html__( 'Vynulovať', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div style="margin-bottom: 20px;">
            <h3><?php echo esc_html__( 'Výsledky vyhľadávania', HELPDESK_TEXT_DOMAIN ); ?></h3>
            
            <div id="solution-results-container" style="background: #fff; border: 1px solid #ccc; border-radius: 4px; padding: 20px;">
                <div id="solution-results-loading" style="text-align: center; display: none;">
                    <p><?php echo esc_html__( 'Načítavam...', HELPDESK_TEXT_DOMAIN ); ?></p>
                </div>

                <div id="solution-results-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px;">
                    <?php if ( ! empty( $guides ) ) : ?>
                        <?php foreach ( $guides as $guide ) : ?>
                            <div class="solution-card" data-guide-id="<?php echo absint( $guide->id ); ?>" data-product="<?php echo esc_attr( $guide->produkt ?? '' ); ?>" data-problem="<?php echo esc_attr( $guide->problem_id ?? '' ); ?>" data-category="<?php echo esc_attr( $guide->kategoria ?? '' ); ?>" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #f9f9f9; cursor: pointer; transition: all 0.3s ease;">
                                <h4 style="margin-top: 0; color: #0073aa;">
                                    <?php echo esc_html( $guide->nazov ); ?>
                                </h4>
                                
                                <div style="margin-bottom: 10px;">
                                    <span style="display: inline-block; background: #e7f3ff; color: #0073aa; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">
                                        <?php echo esc_html( $guide->kategoria ?? 'Bez typu' ); ?>
                                    </span>
                                    <?php if ( ! empty( $guide->produkt ) ) : ?>
                                        <?php 
                                        $product = array_filter( $products, function( $p ) use ( $guide ) {
                                            return $p['id'] == $guide->produkt;
                                        } );
                                        if ( $product ) {
                                            $product_obj = reset( $product );
                                            echo '<span style="display: inline-block; background: #fff8e5; color: #856404; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">' . esc_html( $product_obj['nazov'] ) . '</span>';
                                        }
                                        ?>
                                    <?php endif; ?>
                                </div>

                                <p style="margin: 10px 0; color: #666; font-size: 14px; line-height: 1.4;">
                                    <?php echo esc_html( substr( $guide->popis, 0, 150 ) ); ?>...
                                </p>

                                <?php if ( ! empty( $guide->tagy ) ) : ?>
                                    <div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">
                                        <?php 
                                        $tagy = json_decode( $guide->tagy, true );
                                        if ( is_array( $tagy ) && ! empty( $tagy ) ) {
                                            foreach ( array_slice( $tagy, 0, 3 ) as $tag ) {
                                                echo '<span style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 3px; margin-bottom: 3px;">#' . esc_html( $tag ) . '</span>';
                                            }
                                        }
                                        ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php else : ?>
                        <div style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                            <p><?php echo esc_html__( 'Žiadne riešenia k dispozícii', HELPDESK_TEXT_DOMAIN ); ?></p>
                        </div>
                    <?php endif; ?>
                </div>

                <div id="solution-no-results" style="text-align: center; padding: 40px; display: none;">
                    <p><?php echo esc_html__( 'Žiadne riešenia nebyly nájdené podľa zadaných kritérií.', HELPDESK_TEXT_DOMAIN ); ?></p>
                </div>
            </div>
        </div>

        <!-- Solution Detail Modal -->
        <div id="solution-detail-modal" class="helpdesk-modal" style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999; align-items: center; justify-content: center;">
            <div class="helpdesk-modal-content" style="width: 90%; max-width: 800px; max-height: 90vh; overflow-y: auto; background: white; border-radius: 8px; padding: 30px; box-shadow: 0 4px 6px rgba(0,0,0,0.1);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <h2 id="solution-modal-title" style="margin: 0;"></h2>
                    <button class="helpdesk-modal-close" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #666;">×</button>
                </div>

                <div id="solution-modal-body" style="line-height: 1.6; color: #333;">
                    <!-- Dynamically populated -->
                </div>

                <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #ddd; display: flex; gap: 10px;">
                    <a id="solution-edit-btn" href="#" class="button button-primary">
                        <?php echo esc_html__( '✏️ Upraviť', HELPDESK_TEXT_DOMAIN ); ?>
                    </a>
                    <button class="helpdesk-modal-close button" style="cursor: pointer;">
                        <?php echo esc_html__( 'Zatvoriť', HELPDESK_TEXT_DOMAIN ); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .solution-card:hover {
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        transform: translateY(-2px);
    }

    .helpdesk-modal {
        display: none !important;
    }

    .helpdesk-modal.show {
        display: flex !important;
    }

    .helpdesk-modal-close {
        cursor: pointer;
    }

    @media (max-width: 768px) {
        #solution-results-list {
            grid-template-columns: 1fr !important;
        }
    }
</style>

<script>
(function($) {
    'use strict';

    $(document).ready(function() {
        if (typeof helpdesk === 'undefined') {
            return;
        }

        const nonce = helpdesk.nonce;
        const ajaxurl = helpdesk.ajaxurl;

        // Search solutions button
        $('#btn-search-solutions').on('click', function() {
            searchSolutions();
        });

        // Reset filters button
        $('#btn-reset-solutions-filters').on('click', function() {
            $('#solution-search-text').val('');
            $('#solution-filter-product').val('');
            $('#solution-filter-problem').val('');
            $('#solution-filter-category').val('');
            $('#solution-results-list').html('');
            $('#solution-no-results').hide();
        });

        // Search on Enter key
        $('#solution-search-text').on('keypress', function(e) {
            if (e.which === 13) {
                searchSolutions();
                return false;
            }
        });

        // Click on card to show details
        $(document).on('click', '.solution-card', function() {
            const guideId = $(this).data('guide-id');
            showSolutionDetail(guideId);
        });

        // Close modal on close button click
        $(document).on('click', '.helpdesk-modal-close', function() {
            $('#solution-detail-modal').removeClass('show').css('display', 'none');
        });

        // Close modal when clicking outside (on backdrop)
        $(document).on('click', '#solution-detail-modal', function(e) {
            if (e.target === this) {
                $(this).removeClass('show').css('display', 'none');
            }
        });

        // Close modal on Escape key
        $(document).on('keydown', function(e) {
            if (e.keyCode === 27) { // Escape key
                $('#solution-detail-modal').removeClass('show').css('display', 'none');
            }
        });

        function searchSolutions() {
            const filters = {
                search: $('#solution-search-text').val(),
                produkt: $('#solution-filter-product').val(),
                problem_id: $('#solution-filter-problem').val(),
                kategoria: $('#solution-filter-category').val()
            };

            $('#solution-results-loading').show();
            $('#solution-results-list').html('');
            $('#solution-no-results').hide();

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'helpdesk_search_guides_by_filters',
                    _wpnonce: nonce,
                    ...filters
                },
                dataType: 'json',
                success: function(response) {
                    $('#solution-results-loading').hide();

                    if (response.success && response.data.guides) {
                        const guides = response.data.guides;

                        if (guides.length === 0) {
                            $('#solution-no-results').show();
                        } else {
                            let html = '';
                            guides.forEach(function(guide) {
                                html += renderSolutionCard(guide);
                            });
                            $('#solution-results-list').html(html);
                        }
                    } else {
                        $('#solution-no-results').show();
                    }
                },
                error: function() {
                    $('#solution-results-loading').hide();
                    $('#solution-no-results').show();
                }
            });
        }

        function renderSolutionCard(guide) {
            const tagy = guide.tagy ? (typeof guide.tagy === 'string' ? JSON.parse(guide.tagy) : guide.tagy) : [];
            const tagyHtml = tagy.slice(0, 3).map(t => 
                '<span style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 2px 6px; border-radius: 3px; font-size: 11px; margin-right: 3px; margin-bottom: 3px;">#' + escapeHtml(t) + '</span>'
            ).join('');

            return `
                <div class="solution-card" data-guide-id="${guide.id}" style="border: 1px solid #ddd; border-radius: 4px; padding: 15px; background: #f9f9f9; cursor: pointer; transition: all 0.3s ease;">
                    <h4 style="margin-top: 0; color: #0073aa;">${escapeHtml(guide.nazov)}</h4>
                    
                    <div style="margin-bottom: 10px;">
                        <span style="display: inline-block; background: #e7f3ff; color: #0073aa; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">
                            ${escapeHtml(guide.kategoria || 'Bez typu')}
                        </span>
                    </div>

                    <p style="margin: 10px 0; color: #666; font-size: 14px; line-height: 1.4;">
                        ${escapeHtml(guide.popis.substring(0, 150))}...
                    </p>

                    ${tagyHtml ? `<div style="margin-top: 10px; padding-top: 10px; border-top: 1px solid #ddd;">${tagyHtml}</div>` : ''}
                </div>
            `;
        }

        function showSolutionDetail(guideId) {
            $.ajax({
                url: ajaxurl,
                type: 'GET',
                data: {
                    action: 'helpdesk_get_general_guide',
                    nonce: nonce,
                    id: guideId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success && response.data.guide) {
                        const guide = response.data.guide;
                        $('#solution-modal-title').text(guide.nazov);
                        
                        const tagy = guide.tagy ? (typeof guide.tagy === 'string' ? JSON.parse(guide.tagy) : guide.tagy) : [];
                        const tagyHtml = tagy.map(t => 
                            '<span style="display: inline-block; background: #e8f5e9; color: #2e7d32; padding: 4px 8px; border-radius: 3px; font-size: 12px; margin-right: 5px;">#' + escapeHtml(t) + '</span>'
                        ).join('');

                        const bodyHtml = `
                            <div style="margin-bottom: 20px;">
                                <strong>Typ:</strong> ${escapeHtml(guide.kategoria || 'Bez typu')}
                            </div>

                            <div style="margin-bottom: 20px;">
                                <strong>Popis:</strong>
                                <p style="color: #555; white-space: pre-wrap;">${escapeHtml(guide.popis)}</p>
                            </div>

                            ${tagyHtml ? `
                                <div style="margin-bottom: 20px;">
                                    <strong>Tagy:</strong>
                                    <div style="margin-top: 10px;">
                                        ${tagyHtml}
                                    </div>
                                </div>
                            ` : ''}
                        `;

                        $('#solution-modal-body').html(bodyHtml);
                        $('#solution-edit-btn').attr('href', '#').click(function(e) {
                            e.preventDefault();
                            // Navigate to edit page or trigger edit modal
                            window.location.href = '?page=helpdesk-general-guides&edit=' + guideId;
                        });

                        $('#solution-detail-modal').addClass('show').css('display', 'flex');
                    }
                }
            });
        }

        function escapeHtml(text) {
            if (!text) return '';
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    });
})(jQuery);
</script>
