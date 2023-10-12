<?php

namespace Drupal\views_load_more\EventSubscriber;


use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Drupal\views\Ajax\ViewAjaxResponse;
use Drupal\views_load_more\Ajax\VLMAppendCommand;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class VLMEventSubscriber implements EventSubscriberInterface {

  /**
   * {@inheritdoc}
   */
  public static function getSubscribedEvents() {
    $events[KernelEvents::RESPONSE][] = array('onResponse', 0);

    return $events;
  }

  /**
   * Filter a views AJAX response when the Load More pager is set. Remove the
   * scrollTop and viewsScrollTop command and add in a viewsLoadMoreAppend AJAX command.
   *
   * @param \Symfony\Component\HttpKernel\Event\ResponseEvent $event
   */
  public function onResponse(ResponseEvent $event) {
    $response = $event->getResponse();

    if ($response instanceof ViewAjaxResponse) {
      $view = $response->getView();
      $pagerPlugin = $view->getPager();

      if ($pagerPlugin->getPluginId() == 'load_more' && $pagerPlugin->getCurrentPage() > 0) {
        $commands =& $response->getCommands();

        foreach ($commands as $key => $command) {
          // Remove 'scrollTop' and 'viewsScrollTop' command, as this behavior is unnecessary.
          if (in_array($command['command'], ['scrollTop', 'viewsScrollTop'])) {
            unset($commands[$key]);
          }
          // The replace should be the only insert command, but just in case, we'll make sure.
          else if ($command['command'] == 'insert' && $command['method'] == 'replaceWith' && $command['selector'] == '.js-view-dom-id-' . $view->dom_id) {
            $stylePlugin = $view->getStyle();
            // Take the data attribute, which is the content of the view,
            // otherwise discard the insert command for the view, we're
            // replacing it with a VLMAppendCommand
            $content = $commands[$key]['data'];
            $cmd_options = array(
              'wrapper_selector' => $commands[$key]['selector'],
              // Changes to the content and pager selectors, if any required by
              // theme.
              'content_selector' => $pagerPlugin->options['advanced']['content_selector'],
              'pager_selector' => $pagerPlugin->options['advanced']['pager_selector'],
              // Animation effects
              'effect' => $pagerPlugin->options['effects']['type'],
              'speed' => $pagerPlugin->options['effects']['speed'],
            );
            unset($commands[$key]);

            // Special case for lists and tables
            if ($stylePlugin->getPluginId() == 'html_list' && in_array($stylePlugin->options['type'], array('ul', 'ol'))) {
              if (empty($stylePlugin->options['wrapper_class'])) {
                $cmd_options['target_list'] = "> div > {$stylePlugin->options['type']}:not(.links)";
              }
              else {
                $wrapper_classes = explode(' ', $stylePlugin->options['wrapper_class']);
                $wrapper_classes = implode('.', $wrapper_classes);
                $cmd_options['target_list'] = ".{$wrapper_classes} > {$stylePlugin->options['type']}:not(.links)";
              }
            }
            else if ($stylePlugin->getPluginId() == 'table') {
              $cmd_options['target_list'] = '.views-table tbody';
            }

            $response->addCommand(new VLMAppendCommand($content, array_filter($cmd_options)));
          }
        }
      }
    }
  }
}
